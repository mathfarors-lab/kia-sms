<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Support\BranchContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * M2 owner console: consolidated dashboard, branch management, per-branch
 * settings, and the "All branches" report toggle. Branch isolation itself
 * (list/direct-URL/sequence/settings-fallback) is covered by
 * BranchIsolationTest — this file covers what M2 adds on top of that
 * foundation.
 */
class OwnerConsoleTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchA;
    private Branch $branchB;
    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');

        $this->branchA = Branch::findOrFail(1);
        $this->branchB = Branch::create(['name_en' => 'Riverside Campus', 'code' => 'RC', 'is_active' => true]);
        $this->year = AcademicYear::create([
            'name' => 'Y-' . uniqid(), 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        BranchContext::clear();
    }

    protected function tearDown(): void
    {
        BranchContext::clear();
        parent::tearDown();
    }

    private function makeOwner(): User
    {
        $user = User::factory()->create(['status' => 'active', 'branch_id' => null]);
        $user->assignRole('owner');
        return $user;
    }

    private function makeAdminOf(Branch $branch): User
    {
        $user = User::factory()->create(['status' => 'active', 'branch_id' => $branch->id]);
        $user->assignRole('admin');
        return $user;
    }

    /** Creates $count enrolled students in $branch with an invoice+payment each, inside that branch's context. */
    private function seedBranchData(Branch $branch, int $studentCount, float $paymentEach = 100.0): void
    {
        BranchContext::within($branch->id, function () use ($branch, $studentCount, $paymentEach) {
            $class = SchoolClass::create(['name' => "{$branch->code}-Class"]);
            $section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);

            for ($i = 0; $i < $studentCount; $i++) {
                $student = Student::create([
                    'student_code' => 'S-' . uniqid(), 'name_en' => "{$branch->code} Student {$i}",
                    'gender' => 'male', 'status' => 'enrolled',
                ]);
                $student->sections()->attach($section->id, ['academic_year_id' => $this->year->id]);

                $invoice = Invoice::create([
                    'number' => 'INV-' . uniqid(), 'student_id' => $student->id,
                    'academic_year_id' => $this->year->id, 'term' => 'term_1',
                    'subtotal' => $paymentEach, 'discount' => 0, 'total' => $paymentEach, 'paid' => $paymentEach, 'status' => 'paid',
                ]);
                Payment::create([
                    'invoice_id' => $invoice->id, 'amount' => $paymentEach, 'method' => 'cash', 'paid_at' => now(),
                ]);
            }
        });
    }

    // ── Owner dashboard: consolidated across branches ─────────────────────────

    public function test_owner_dashboard_shows_correct_per_branch_figures(): void
    {
        $this->seedBranchData($this->branchA, 2, 100.0);
        $this->seedBranchData($this->branchB, 5, 50.0);

        $html = $this->actingAs($this->makeOwner())
            ->get(route('owner.dashboard'))
            ->assertOk()
            ->getContent();

        // Branch A: 2 students, $200 revenue. Branch B: 5 students, $250 revenue.
        $this->assertMatchesRegularExpression('/Main Campus.*?<\/td>\s*<td>\s*<span[^>]*>\s*Active/s', $html);
        $this->assertStringContainsString('200.00', $html);
        $this->assertStringContainsString('250.00', $html);

        // Grand totals: 7 students, $450 combined.
        $this->assertStringContainsString('450.00', $html);
    }

    public function test_owner_dashboard_totals_are_not_scoped_to_currently_switched_branch(): void
    {
        $this->seedBranchData($this->branchA, 3);
        $this->seedBranchData($this->branchB, 4);

        $owner = $this->makeOwner();
        $this->actingAs($owner)->post(route('branch.switch'), ['branch_id' => $this->branchA->id]);

        $html = $this->actingAs($owner)->get(route('owner.dashboard'))->assertOk()->getContent();

        // 3 + 4 = 7 total students, not just branch A's 3.
        $this->assertStringContainsString('>7<', $html);
    }

    public function test_non_owner_cannot_view_owner_dashboard(): void
    {
        $this->actingAs($this->makeAdminOf($this->branchA))
            ->get(route('owner.dashboard'))
            ->assertForbidden();
    }

    // ── Branch CRUD ──────────────────────────────────────────────────────────

    public function test_owner_can_create_branch_with_unique_code(): void
    {
        $this->actingAs($this->makeOwner())->post(route('owner.branches.store'), [
            'name_en' => 'North Campus', 'name_km' => 'សាខាខាងជើង', 'code' => 'NC', 'address' => 'Siem Reap',
        ])->assertRedirect(route('owner.branches.index'));

        $this->assertDatabaseHas('branches', ['code' => 'NC', 'name_en' => 'North Campus']);
    }

    public function test_duplicate_branch_code_is_rejected(): void
    {
        $this->actingAs($this->makeOwner())
            ->post(route('owner.branches.store'), ['name_en' => 'Duplicate', 'code' => 'RC'])
            ->assertSessionHasErrors('code');

        $this->assertEquals(1, Branch::where('code', 'RC')->count());
    }

    public function test_branch_code_is_uppercased(): void
    {
        $this->actingAs($this->makeOwner())->post(route('owner.branches.store'), [
            'name_en' => 'Lowercase Test', 'code' => 'lc',
        ]);

        $this->assertDatabaseHas('branches', ['code' => 'LC']);
    }

    public function test_owner_can_upload_branch_logo(): void
    {
        $this->actingAs($this->makeOwner())->post(route('owner.branches.store'), [
            'name_en' => 'Logo Branch', 'code' => 'LB',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ]);

        $branch = Branch::where('code', 'LB')->firstOrFail();
        $this->assertNotNull($branch->logo_path);
        Storage::disk('local')->assertExists($branch->logo_path);

        $this->actingAs($this->makeOwner())->get(route('branches.logo', $branch))->assertOk();
    }

    public function test_non_owner_cannot_create_branch(): void
    {
        $this->actingAs($this->makeAdminOf($this->branchA))
            ->post(route('owner.branches.store'), ['name_en' => 'Nope', 'code' => 'NO'])
            ->assertForbidden();

        $this->assertDatabaseMissing('branches', ['code' => 'NO']);
    }

    public function test_non_owner_cannot_reach_branches_index(): void
    {
        $this->actingAs($this->makeAdminOf($this->branchA))
            ->get(route('owner.branches.index'))
            ->assertForbidden();
    }

    // ── Suspend / reactivate ────────────────────────────────────────────────

    public function test_owner_can_suspend_and_reactivate_a_branch(): void
    {
        $owner = $this->makeOwner();

        $this->actingAs($owner)->post(route('owner.branches.toggle-active', $this->branchB));
        $this->assertFalse($this->branchB->fresh()->is_active);

        $this->actingAs($owner)->post(route('owner.branches.toggle-active', $this->branchB));
        $this->assertTrue($this->branchB->fresh()->is_active);
    }

    public function test_suspended_branch_blocks_its_users_but_not_owner_or_other_branches(): void
    {
        $this->seedBranchData($this->branchB, 2);
        $bAdmin = $this->makeAdminOf($this->branchB);
        $aAdmin = $this->makeAdminOf($this->branchA);
        $owner = $this->makeOwner();

        $this->branchB->update(['is_active' => false]);

        // Branch B's own admin is blocked on every authenticated page.
        $this->actingAs($bAdmin)
            ->get(route('dashboard'))
            ->assertForbidden()
            ->assertSee(__('branches.suspended_title'));

        // Branch A is unaffected. (route('dashboard') itself always 302s to
        // the role-specific dashboard by design — assert the real destination.)
        $this->actingAs($aAdmin)->get(route('dashboard.admin'))->assertOk();

        // Owner is never blocked, and can still switch into B to see its data.
        $this->actingAs($owner)->post(route('branch.switch'), ['branch_id' => $this->branchB->id]);
        $this->actingAs($owner)->get(route('owner.dashboard'))->assertOk();
    }

    public function test_suspended_branch_historical_data_stays_intact_and_owner_visible(): void
    {
        $this->seedBranchData($this->branchB, 3, 75.0);
        $this->branchB->update(['is_active' => false]);

        $studentCountBefore = BranchContext::within($this->branchB->id, fn () => Student::count());
        $this->assertEquals(3, $studentCountBefore);

        $html = $this->actingAs($this->makeOwner())
            ->get(route('owner.dashboard'))
            ->assertOk()
            ->getContent();

        // 3 students × $75 = $225, still counted even though the branch is suspended.
        $this->assertStringContainsString('225.00', $html);
    }

    public function test_blocked_user_can_still_log_out(): void
    {
        $bAdmin = $this->makeAdminOf($this->branchB);
        $this->branchB->update(['is_active' => false]);

        $this->actingAs($bAdmin)->post(route('logout'))->assertRedirect();
    }

    // ── Admin appointment ────────────────────────────────────────────────────

    public function test_owner_can_appoint_existing_user_as_branch_admin(): void
    {
        $teacher = User::factory()->create(['status' => 'active', 'branch_id' => $this->branchA->id]);
        $teacher->assignRole('teacher');

        $this->actingAs($this->makeOwner())
            ->post(route('owner.branches.admins.appoint', $this->branchB), ['existing_email' => $teacher->email])
            ->assertRedirect(route('owner.branches.admins', $this->branchB));

        $teacher->refresh();
        $this->assertTrue($teacher->hasRole('admin'));
        $this->assertEquals($this->branchB->id, $teacher->branch_id);
    }

    public function test_owner_can_appoint_brand_new_branch_admin(): void
    {
        $this->actingAs($this->makeOwner())->post(route('owner.branches.admins.appoint', $this->branchB), [
            'new_name' => 'New Admin', 'new_email' => 'newadmin@kia.edu.kh',
        ])->assertRedirect();

        $user = User::where('email', 'newadmin@kia.edu.kh')->firstOrFail();
        $this->assertTrue($user->hasRole('admin'));
        $this->assertEquals($this->branchB->id, $user->branch_id);
    }

    public function test_appointed_admin_can_only_manage_their_own_branch(): void
    {
        // Extends BranchIsolationTest's pattern: appointment must produce a
        // genuinely branch-locked admin, not a de-facto second owner.
        $teacher = User::factory()->create(['status' => 'active', 'branch_id' => $this->branchA->id]);
        $teacher->assignRole('teacher');
        $this->actingAs($this->makeOwner())
            ->post(route('owner.branches.admins.appoint', $this->branchB), ['existing_email' => $teacher->email]);
        $newAdmin = $teacher->fresh();

        $aStudent = BranchContext::within($this->branchA->id, fn () => Student::create([
            'student_code' => 'S-' . uniqid(), 'name_en' => 'Branch A Student', 'gender' => 'male', 'status' => 'enrolled',
        ]));

        $this->actingAs($newAdmin)
            ->get(route('students.show', $aStudent))
            ->assertNotFound();

        $this->actingAs($newAdmin)->get(route('owner.branches.index'))->assertForbidden();
    }

    public function test_owner_can_remove_branch_admin_without_deleting_the_user(): void
    {
        $admin = $this->makeAdminOf($this->branchB);

        $this->actingAs($this->makeOwner())
            ->delete(route('owner.branches.admins.remove', [$this->branchB, $admin]))
            ->assertRedirect();

        $admin->refresh();
        $this->assertFalse($admin->hasRole('admin'));
        $this->assertNotNull(User::find($admin->id)); // user record untouched
    }

    public function test_appointing_unknown_email_fails_cleanly(): void
    {
        $this->actingAs($this->makeOwner())
            ->post(route('owner.branches.admins.appoint', $this->branchB), ['existing_email' => 'ghost@nowhere.com'])
            ->assertSessionHasErrors('existing_email');
    }

    public function test_non_owner_cannot_appoint_admins(): void
    {
        $this->actingAs($this->makeAdminOf($this->branchA))
            ->post(route('owner.branches.admins.appoint', $this->branchB), ['existing_email' => 'x@x.com'])
            ->assertForbidden();
    }

    // ── Per-branch settings ──────────────────────────────────────────────────

    public function test_settings_page_shows_only_current_branch_value_not_a_mix(): void
    {
        BranchContext::within($this->branchA->id, fn () => Setting::set('school_name', 'Main Campus School'));
        BranchContext::within($this->branchB->id, fn () => Setting::set('school_name', 'Riverside School'));

        $htmlA = $this->actingAs($this->makeAdminOf($this->branchA))
            ->get(route('settings.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Main Campus School', $htmlA);
        $this->assertStringNotContainsString('Riverside School', $htmlA);

        $htmlB = $this->actingAs($this->makeAdminOf($this->branchB))
            ->get(route('settings.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Riverside School', $htmlB);
        $this->assertStringNotContainsString('Main Campus School', $htmlB);
    }

    public function test_settings_page_falls_back_to_global_row_when_branch_has_no_override(): void
    {
        BranchContext::within(null, fn () => Setting::set('pass_mark', '50'));

        $html = $this->actingAs($this->makeAdminOf($this->branchA))
            ->get(route('settings.index'))->assertOk()->getContent();

        $this->assertStringContainsString('value="50"', $html);
    }

    public function test_settings_never_renders_two_inputs_with_the_same_name(): void
    {
        BranchContext::within($this->branchA->id, fn () => Setting::set('school_name', 'A Name'));
        BranchContext::within($this->branchB->id, fn () => Setting::set('school_name', 'B Name'));

        $html = $this->actingAs($this->makeAdminOf($this->branchA))
            ->get(route('settings.index'))->assertOk()->getContent();

        $this->assertEquals(1, substr_count($html, 'name="settings[school_name]"'));
    }

    // ── Consolidated report exports ─────────────────────────────────────────

    public function test_all_branches_report_includes_both_branches_with_branch_column(): void
    {
        $this->seedBranchData($this->branchA, 1);
        $this->seedBranchData($this->branchB, 1);

        $html = $this->actingAs($this->makeOwner())
            ->get(route('reports.enrollment', ['year_id' => $this->year->id, 'all_branches' => 1]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Main Campus', $html);
        $this->assertStringContainsString('Riverside Campus', $html);
    }

    public function test_report_without_all_branches_stays_scoped_to_viewers_own_branch(): void
    {
        $this->seedBranchData($this->branchA, 1);
        $this->seedBranchData($this->branchB, 1);

        $html = $this->actingAs($this->makeAdminOf($this->branchA))
            ->get(route('reports.enrollment', ['year_id' => $this->year->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('MC Student 0', $html);
        $this->assertStringNotContainsString('RC Student 0', $html);
    }

    public function test_non_owner_all_branches_param_is_ignored_not_honored(): void
    {
        $this->seedBranchData($this->branchA, 1);
        $this->seedBranchData($this->branchB, 1);

        // A branch-A admin passing all_branches=1 must NOT see branch B's data.
        $html = $this->actingAs($this->makeAdminOf($this->branchA))
            ->get(route('reports.enrollment', ['year_id' => $this->year->id, 'all_branches' => 1]))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('RC Student 0', $html);
    }

    public function test_all_branches_fee_report_pdf_and_csv_render_without_error(): void
    {
        $this->seedBranchData($this->branchA, 1);
        $this->seedBranchData($this->branchB, 1);
        $owner = $this->makeOwner();

        $this->actingAs($owner)
            ->get(route('reports.fees', ['year_id' => $this->year->id, 'all_branches' => 1, 'format' => 'pdf']))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('reports.fees', ['year_id' => $this->year->id, 'all_branches' => 1, 'format' => 'excel']))
            ->assertOk();
    }

    public function test_all_branches_attendance_report_pdf_renders_without_error(): void
    {
        $this->seedBranchData($this->branchA, 1);

        $this->actingAs($this->makeOwner())
            ->get(route('reports.attendance', ['year_id' => $this->year->id, 'all_branches' => 1, 'format' => 'pdf']))
            ->assertOk();
    }
}
