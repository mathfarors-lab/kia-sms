<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        // array cache store persists across tests within one process —
        // flush so a stale cached figure from a prior test can't leak in.
        Cache::flush();
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('admin');
        return $user;
    }

    private function makeYear(): AcademicYear
    {
        return AcademicYear::create([
            'name' => 'Test Year', 'start_date' => '2026-01-01',
            'end_date' => '2026-12-31', 'is_active' => true,
        ]);
    }

    private function makeSection(AcademicYear $year): Section
    {
        $class = SchoolClass::create(['name' => 'Grade 10']);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);
        return $section;
    }

    private function makeStudent(Section $section, AcademicYear $year): Student
    {
        $student = Student::create([
            'student_code' => 'S-' . uniqid(),
            'name_en'      => 'Student-' . uniqid(),
            'gender'       => 'male',
            'status'       => 'enrolled',
        ]);
        $section->students()->attach($student->id, ['academic_year_id' => $year->id]);
        return $student;
    }

    // ── Revenue this month ─────────────────────────────────────────────────

    public function test_dashboard_shows_correct_revenue_this_month(): void
    {
        $admin = $this->makeAdmin();
        $year  = $this->makeYear();
        $section = $this->makeSection($year);
        $student = $this->makeStudent($section, $year);

        $invoice = Invoice::create([
            'number' => 'INV-TEST-1', 'student_id' => $student->id, 'academic_year_id' => $year->id,
            'term' => 'term_1', 'subtotal' => 100, 'discount' => 0, 'total' => 100,
            'paid' => 100, 'status' => 'paid',
        ]);

        // Counted: paid this month.
        Payment::create([
            'invoice_id' => $invoice->id, 'amount' => 150.50, 'method' => 'cash',
            'received_by' => $admin->id, 'paid_at' => now(),
        ]);
        // Excluded: paid last month.
        Payment::create([
            'invoice_id' => $invoice->id, 'amount' => 999.00, 'method' => 'cash',
            'received_by' => $admin->id, 'paid_at' => now()->subMonthsNoOverflow(2),
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard.admin'))
            ->assertOk()
            ->assertSee('$150.50');
    }

    // ── Attendance today ─────────────────────────────────────────────────

    public function test_dashboard_shows_correct_attendance_rate_today(): void
    {
        $admin   = $this->makeAdmin();
        $year    = $this->makeYear();
        $section = $this->makeSection($year);

        // 3 present, 1 absent today -> 75%.
        foreach (['present', 'present', 'present', 'absent'] as $status) {
            $student = $this->makeStudent($section, $year);
            Attendance::create([
                'student_id' => $student->id, 'section_id' => $section->id,
                'date' => now()->toDateString(), 'status' => $status, 'marked_by' => $admin->id,
            ]);
        }

        // Yesterday's record must not affect today's rate.
        $otherStudent = $this->makeStudent($section, $year);
        Attendance::create([
            'student_id' => $otherStudent->id, 'section_id' => $section->id,
            'date' => now()->subDay()->toDateString(), 'status' => 'absent', 'marked_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard.admin'))
            ->assertOk()
            ->assertSee('75%');
    }

    public function test_dashboard_shows_no_data_yet_when_no_attendance_marked_today(): void
    {
        $admin = $this->makeAdmin();
        $this->makeYear();

        $this->actingAs($admin)
            ->get(route('dashboard.admin'))
            ->assertOk()
            ->assertSee(__('admin_dashboard.no_attendance_yet'));
    }

    // ── Recent activity ──────────────────────────────────────────────────

    public function test_dashboard_shows_recent_activity_with_changed_fields(): void
    {
        $admin   = $this->makeAdmin();
        $student = Student::create([
            'student_code' => 'S-' . uniqid(), 'name_en' => 'Original Name',
            'gender' => 'male', 'status' => 'enrolled',
        ]);

        $this->actingAs($admin);
        $student->update(['name_en' => 'Updated Name']);

        $this->actingAs($admin)
            ->get(route('dashboard.admin'))
            ->assertOk()
            ->assertSee('Student')
            ->assertSee('name_en');
    }

    public function test_dashboard_shows_empty_state_when_no_activity_logged(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('dashboard.admin'))
            ->assertOk()
            ->assertSee(__('No recent activity'));
    }

    public function test_dashboard_strips_sensitive_fields_from_activity_feed(): void
    {
        $admin = $this->makeAdmin();

        Activity::create([
            'log_name'     => 'default',
            'description'  => 'updated',
            'subject_type' => User::class,
            'subject_id'   => $admin->id,
            'causer_type'  => User::class,
            'causer_id'    => $admin->id,
            'properties'   => [
                'attributes' => ['password' => 'hashed-secret-value', 'name' => 'Changed Name'],
            ],
        ]);

        $html = $this->actingAs($admin)
            ->get(route('dashboard.admin'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('hashed-secret-value', $html);
        $this->assertStringNotContainsString('password', $html);
        $this->assertStringContainsString('name', $html);
    }

    // ── Regression: finance dashboard still works after the refactor ──────

    public function test_finance_dashboard_still_shows_collected_this_month(): void
    {
        $admin = $this->makeAdmin();
        $admin->givePermissionTo('invoices.view');
        $year  = $this->makeYear();
        $section = $this->makeSection($year);
        $student = $this->makeStudent($section, $year);

        $invoice = Invoice::create([
            'number' => 'INV-TEST-2', 'student_id' => $student->id, 'academic_year_id' => $year->id,
            'term' => 'term_1', 'subtotal' => 200, 'discount' => 0, 'total' => 200,
            'paid' => 200, 'status' => 'paid',
        ]);
        Payment::create([
            'invoice_id' => $invoice->id, 'amount' => 200, 'method' => 'cash',
            'received_by' => $admin->id, 'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('finance.dashboard'))
            ->assertOk()
            ->assertSee('200.00');
    }
}
