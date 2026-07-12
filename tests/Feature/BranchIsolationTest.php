<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\User;
use App\Services\InvoiceService;
use App\Support\BranchContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * THE multi-tenancy test class (M1): Branch A must never see Branch B —
 * by list, by direct URL, or by generated sequence collision — while the
 * owner reaches every branch through the switcher.
 */
class BranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchA;
    private Branch $branchB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        // Branch 1 (Main Campus) exists from the migration itself.
        $this->branchA = Branch::findOrFail(1);
        $this->branchB = Branch::create(['name_en' => 'Riverside Campus', 'code' => 'RC', 'is_active' => true]);

        BranchContext::clear();
    }

    protected function tearDown(): void
    {
        BranchContext::clear();
        parent::tearDown();
    }

    private function makeAdminOf(Branch $branch): User
    {
        $user = User::factory()->create(['status' => 'active', 'branch_id' => $branch->id]);
        $user->assignRole('admin');
        return $user;
    }

    private function makeStudentIn(Branch $branch, string $name): Student
    {
        return BranchContext::within($branch->id, fn () => Student::create([
            'student_code' => 'S-' . uniqid(), 'name_en' => $name,
            'gender' => 'male', 'status' => 'enrolled',
        ]));
    }

    // ── List isolation ───────────────────────────────────────────────────────

    public function test_branch_admin_sees_only_own_branch_students_in_lists(): void
    {
        $aStudent = $this->makeStudentIn($this->branchA, 'Alpha Student');
        $bStudent = $this->makeStudentIn($this->branchB, 'Beta Student');

        $html = $this->actingAs($this->makeAdminOf($this->branchA))
            ->get(route('students.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Alpha Student', $html);
        $this->assertStringNotContainsString('Beta Student', $html);
    }

    // ── Direct-URL isolation (the tenancy IDOR) ─────────────────────────────

    public function test_branch_admin_cannot_reach_other_branch_student_by_direct_url(): void
    {
        $bStudent = $this->makeStudentIn($this->branchB, 'Hidden Student');

        // Route-model binding runs through the global scope → 404, record invisible.
        $this->actingAs($this->makeAdminOf($this->branchA))
            ->get(route('students.show', $bStudent->id))
            ->assertNotFound();
    }

    public function test_branch_admin_cannot_reach_other_branch_invoice_by_direct_url(): void
    {
        $year = \App\Models\AcademicYear::create([
            'name' => 'Y-' . uniqid(), 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);
        $bStudent = $this->makeStudentIn($this->branchB, 'Billed Student');
        $bInvoice = BranchContext::within($this->branchB->id, fn () => Invoice::create([
            'number' => 'INV-X-1', 'student_id' => $bStudent->id,
            'academic_year_id' => $year->id, 'term' => 'term_1',
            'subtotal' => 100, 'discount' => 0, 'total' => 100, 'paid' => 0, 'status' => 'unpaid',
        ]));

        $this->actingAs($this->makeAdminOf($this->branchA))
            ->get(route('invoices.show', $bInvoice->id))
            ->assertNotFound();
    }

    // ── Creation stamping ────────────────────────────────────────────────────

    public function test_records_created_by_branch_admin_are_stamped_with_their_branch(): void
    {
        $admin = $this->makeAdminOf($this->branchB);

        $this->actingAs($admin)->post(route('students.store'), [
            'name_en' => 'Stamped Student', 'gender' => 'male', 'status' => 'enrolled',
        ]);

        $student = Student::withoutGlobalScopes()->where('name_en', 'Stamped Student')->firstOrFail();
        $this->assertEquals($this->branchB->id, $student->branch_id);
    }

    // ── Owner: switcher grants access per selected branch ────────────────────

    public function test_owner_sees_selected_branch_and_switcher_changes_scope(): void
    {
        $this->makeStudentIn($this->branchA, 'Alpha Student');
        $this->makeStudentIn($this->branchB, 'Beta Student');

        $owner = User::factory()->create(['status' => 'active', 'branch_id' => null]);
        $owner->assignRole('owner');

        // Default: first active branch (A).
        $html = $this->actingAs($owner)->get(route('students.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Alpha Student', $html);
        $this->assertStringNotContainsString('Beta Student', $html);

        // Switch to B.
        $this->actingAs($owner)
            ->post(route('branch.switch'), ['branch_id' => $this->branchB->id])
            ->assertRedirect();

        $html = $this->actingAs($owner)->get(route('students.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Beta Student', $html);
        $this->assertStringNotContainsString('Alpha Student', $html);
    }

    public function test_non_owner_cannot_use_branch_switcher(): void
    {
        $this->actingAs($this->makeAdminOf($this->branchA))
            ->post(route('branch.switch'), ['branch_id' => $this->branchB->id])
            ->assertForbidden();
    }

    // ── Per-branch sequences ─────────────────────────────────────────────────

    public function test_invoice_numbers_are_per_branch_and_never_collide(): void
    {
        $service = app(InvoiceService::class);

        $numberA = BranchContext::within($this->branchA->id, fn () => $service->nextNumber());
        $numberB = BranchContext::within($this->branchB->id, fn () => $service->nextNumber());

        $this->assertNotEquals($numberA, $numberB);
        $this->assertStringContainsString('MC', $numberA); // Main Campus code embedded
        $this->assertStringContainsString('RC', $numberB); // Riverside code embedded
    }

    // ── Per-branch settings ──────────────────────────────────────────────────

    public function test_settings_are_per_branch_with_global_fallback(): void
    {
        BranchContext::within(null, fn () => \App\Models\Setting::set('school_name', 'KIA Global'));
        BranchContext::within($this->branchB->id, fn () => \App\Models\Setting::set('school_name', 'KIA Riverside'));

        $this->assertEquals('KIA Riverside', BranchContext::within($this->branchB->id, fn () => \App\Models\Setting::get('school_name')));
        // Branch A has no own value → inherits the global row.
        $this->assertEquals('KIA Global', BranchContext::within($this->branchA->id, fn () => \App\Models\Setting::get('school_name')));
    }

    // ── Analytics/report isolation (raw DB::table() queries bypass the ───────
    // ── Eloquent BranchScope entirely — this is where a real leak was found) ─

    public function test_analytics_overview_counts_only_own_branch_students(): void
    {
        $year = \App\Models\AcademicYear::create([
            'name' => 'Y-' . uniqid(), 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        BranchContext::within($this->branchA->id, function () use ($year) {
            $class   = \App\Models\SchoolClass::create(['name' => 'A-Class']);
            $section = \App\Models\Section::create(['school_class_id' => $class->id, 'name' => 'A']);
            $student = Student::create(['student_code' => 'S-' . uniqid(), 'name_en' => 'A1', 'gender' => 'male', 'status' => 'enrolled']);
            $student->sections()->attach($section->id, ['academic_year_id' => $year->id]);
        });

        BranchContext::within($this->branchB->id, function () use ($year) {
            $class   = \App\Models\SchoolClass::create(['name' => 'B-Class']);
            $section = \App\Models\Section::create(['school_class_id' => $class->id, 'name' => 'B']);
            foreach (['B1', 'B2', 'B3'] as $name) {
                $student = Student::create(['student_code' => 'S-' . uniqid(), 'name_en' => $name, 'gender' => 'male', 'status' => 'enrolled']);
                $student->sections()->attach($section->id, ['academic_year_id' => $year->id]);
            }
        });

        $service = app(\App\Services\AnalyticsService::class);

        $countA = BranchContext::within($this->branchA->id, fn () => $service->overview($year)['enrolledCount']);
        $countB = BranchContext::within($this->branchB->id, fn () => $service->overview($year)['enrolledCount']);

        $this->assertEquals(1, $countA);
        $this->assertEquals(3, $countB);
    }

    public function test_analytics_cache_does_not_leak_between_branches(): void
    {
        // Same cache TTL window, different branches: without a branch-suffixed
        // cache key, branch B's call would be served branch A's cached count.
        Invoice::query(); // ensure autoload before closures
        $year = \App\Models\AcademicYear::create([
            'name' => 'Y-' . uniqid(), 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);
        $service = app(\App\Services\AnalyticsService::class);

        BranchContext::within($this->branchA->id, fn () => \App\Models\Invoice::create([
            'number' => 'INV-A-1', 'student_id' => $this->makeStudentIn($this->branchA, 'Payer A')->id,
            'academic_year_id' => $year->id, 'term' => 'term_1',
            'subtotal' => 100, 'discount' => 0, 'total' => 100, 'paid' => 0, 'status' => 'overdue',
        ]));

        $overdueA = BranchContext::within($this->branchA->id, fn () => $service->overdueInvoiceCount());
        $overdueB = BranchContext::within($this->branchB->id, fn () => $service->overdueInvoiceCount());

        $this->assertEquals(1, $overdueA);
        $this->assertEquals(0, $overdueB);
    }

    public function test_enrollment_report_excludes_other_branch_students(): void
    {
        $year = \App\Models\AcademicYear::create([
            'name' => 'Y-' . uniqid(), 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        BranchContext::within($this->branchA->id, function () use ($year) {
            $class   = \App\Models\SchoolClass::create(['name' => 'A-Class']);
            $section = \App\Models\Section::create(['school_class_id' => $class->id, 'name' => 'A']);
            $student = Student::create(['student_code' => 'S-' . uniqid(), 'name_en' => 'Visible In Report', 'gender' => 'male', 'status' => 'enrolled']);
            $student->sections()->attach($section->id, ['academic_year_id' => $year->id]);
        });

        BranchContext::within($this->branchB->id, function () use ($year) {
            $class   = \App\Models\SchoolClass::create(['name' => 'B-Class']);
            $section = \App\Models\Section::create(['school_class_id' => $class->id, 'name' => 'B']);
            $student = Student::create(['student_code' => 'S-' . uniqid(), 'name_en' => 'Hidden From Report', 'gender' => 'male', 'status' => 'enrolled']);
            $student->sections()->attach($section->id, ['academic_year_id' => $year->id]);
        });

        $html = $this->actingAs($this->makeAdminOf($this->branchA))
            ->get(route('reports.enrollment', ['year_id' => $year->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Visible In Report', $html);
        $this->assertStringNotContainsString('Hidden From Report', $html);
    }

    // ── Legacy/unscoped behavior preserved ───────────────────────────────────

    public function test_user_without_branch_remains_unscoped_pre_m1_behavior(): void
    {
        $this->makeStudentIn($this->branchA, 'Alpha Student');
        $this->makeStudentIn($this->branchB, 'Beta Student');

        $legacyAdmin = User::factory()->create(['status' => 'active', 'branch_id' => null]);
        $legacyAdmin->assignRole('admin');

        $html = $this->actingAs($legacyAdmin)->get(route('students.index'))->assertOk()->getContent();

        // No branch context → no filtering: exactly how the whole app behaved before M1.
        $this->assertStringContainsString('Alpha Student', $html);
        $this->assertStringContainsString('Beta Student', $html);
    }
}
