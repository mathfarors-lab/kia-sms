<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\GateScanLog;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\User;
use App\Notifications\StudentGateEvent;
use App\Support\BranchContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GateScanTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchA;
    private Branch $branchB;
    private AcademicYear $year;
    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->branchA = Branch::findOrFail(1);
        $this->branchB = Branch::create(['name_en' => 'Riverside Campus', 'code' => 'RC', 'is_active' => true]);
        $this->year = AcademicYear::create([
            'name' => 'Y-' . uniqid(), 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        $this->section = BranchContext::within($this->branchA->id, function () {
            $class = SchoolClass::create(['name' => 'Grade 9']);
            return Section::create(['school_class_id' => $class->id, 'name' => 'A']);
        });

        BranchContext::clear();
        Carbon::setTestNow('2026-07-15 07:00:00'); // well before the 07:30 default late cutoff
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        BranchContext::clear();
        parent::tearDown();
    }

    private function makeOperator(Branch $branch): User
    {
        $user = User::factory()->create(['status' => 'active', 'branch_id' => $branch->id]);
        $user->assignRole('receptionist');
        return $user;
    }

    private function makeStudentWithGuardian(Branch $branch, string $code): array
    {
        return BranchContext::within($branch->id, function () use ($code, $branch) {
            $student = Student::create([
                'student_code' => $code, 'name_en' => 'Sokha Test', 'name_km' => 'សុខា',
                'gender' => 'female', 'status' => 'enrolled',
            ]);
            $student->sections()->attach($this->section->id, ['academic_year_id' => $this->year->id]);

            $guardian = User::factory()->create(['status' => 'active', 'phone' => '012345678', 'branch_id' => $branch->id]);
            $guardian->assignRole('parent');
            $student->guardians()->attach($guardian->id, ['relation' => 'mother', 'is_primary' => true]);

            return [$student, $guardian];
        });
    }

    private function makeStaff(Branch $branch, string $code): Staff
    {
        return BranchContext::within($branch->id, function () use ($code, $branch) {
            $user = User::factory()->create(['status' => 'active', 'branch_id' => $branch->id]);
            $user->assignRole('teacher');
            return Staff::create(['user_id' => $user->id, 'staff_code' => $code]);
        });
    }

    private function scan(User $operator, string $code): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($operator)->postJson(route('gate.scan'), ['code' => $code]);
    }

    // ── Core: first scan marks present, second within debounce is a no-op ────

    public function test_scan_marks_present_exactly_once_per_day_debounce_proven(): void
    {
        Notification::fake();
        [$student] = $this->makeStudentWithGuardian($this->branchA, 'KIA-26-0001');
        $operator = $this->makeOperator($this->branchA);

        $r1 = $this->scan($operator, 'KIA-26-0001');
        $r1->assertOk()->assertJson(['result' => 'matched', 'event' => 'arrival']);

        // Rapid second scan, well within the 2-minute debounce window.
        $r2 = $this->scan($operator, 'KIA-26-0001');
        $r2->assertOk()->assertJson(['result' => 'duplicate']);

        $this->assertEquals(1, Attendance::where('student_id', $student->id)->count());
        $this->assertEquals('present', Attendance::where('student_id', $student->id)->value('status'));
        $this->assertEquals('gate_scan', Attendance::where('student_id', $student->id)->value('method'));
    }

    public function test_debounce_window_expires_after_two_minutes(): void
    {
        [$student] = $this->makeStudentWithGuardian($this->branchA, 'KIA-26-0002');
        $operator = $this->makeOperator($this->branchA);

        $this->scan($operator, 'KIA-26-0002')->assertJson(['result' => 'matched']);

        Carbon::setTestNow(Carbon::now()->addMinutes(3));
        $second = $this->scan($operator, 'KIA-26-0002');

        // Past debounce, but departure tracking is off by default → "already
        // recorded" (duplicate), NOT a second arrival — still only one row.
        $second->assertOk()->assertJson(['result' => 'duplicate']);
        $this->assertEquals(1, Attendance::where('student_id', $student->id)->count());
    }

    // ── Late cutoff ──────────────────────────────────────────────────────────

    public function test_scan_after_late_cutoff_marks_late_not_present(): void
    {
        [$student] = $this->makeStudentWithGuardian($this->branchA, 'KIA-26-0003');
        $operator = $this->makeOperator($this->branchA);

        Carbon::setTestNow('2026-07-15 07:45:00'); // past the 07:30 default cutoff

        $this->scan($operator, 'KIA-26-0003')->assertJson(['result' => 'matched', 'event' => 'arrival']);

        $this->assertEquals('late', Attendance::where('student_id', $student->id)->value('status'));
    }

    public function test_scan_before_late_cutoff_marks_present(): void
    {
        [$student] = $this->makeStudentWithGuardian($this->branchA, 'KIA-26-0004');
        $operator = $this->makeOperator($this->branchA);

        $this->scan($operator, 'KIA-26-0004'); // setUp already freezes to 07:00, before cutoff

        $this->assertEquals('present', Attendance::where('student_id', $student->id)->value('status'));
    }

    public function test_branch_specific_late_cutoff_setting_is_honored(): void
    {
        BranchContext::within($this->branchA->id, fn () => Setting::set('gate_late_cutoff', '06:00'));
        [$student] = $this->makeStudentWithGuardian($this->branchA, 'KIA-26-0005');
        $operator = $this->makeOperator($this->branchA);

        // 07:00 (setUp's frozen time) is now past this branch's own 06:00 cutoff.
        $this->scan($operator, 'KIA-26-0005');

        $this->assertEquals('late', Attendance::where('student_id', $student->id)->value('status'));
    }

    // ── Unrecognized QR ──────────────────────────────────────────────────────

    public function test_unrecognized_qr_is_rejected_safely_and_logged(): void
    {
        $operator = $this->makeOperator($this->branchA);

        $response = $this->scan($operator, 'NOT-A-REAL-CODE');

        $response->assertOk()->assertJson(['result' => 'unmatched']);
        $this->assertDatabaseHas('gate_scan_logs', [
            'scanned_code' => 'NOT-A-REAL-CODE', 'result' => 'unmatched', 'student_id' => null, 'staff_id' => null,
        ]);
    }

    public function test_empty_code_does_not_crash_the_station(): void
    {
        $operator = $this->makeOperator($this->branchA);

        $this->actingAs($operator)->postJson(route('gate.scan'), ['code' => ''])
            ->assertStatus(422); // validation rejects it cleanly, no 500
    }

    // ── Cross-branch guard ───────────────────────────────────────────────────

    public function test_cross_branch_card_is_rejected_not_silently_accepted(): void
    {
        [$studentB] = $this->makeStudentWithGuardian($this->branchB, 'KIA-26-0006');
        $operatorAtBranchA = $this->makeOperator($this->branchA);

        $response = $this->scan($operatorAtBranchA, 'KIA-26-0006');

        $response->assertOk()->assertJson(['result' => 'wrong_branch']);
        $this->assertDatabaseHas('gate_scan_logs', [
            'scanned_code' => 'KIA-26-0006', 'result' => 'wrong_branch', 'student_id' => $studentB->id,
        ]);
        // Critically: no attendance was recorded anywhere for this rejected scan.
        $this->assertEquals(0, Attendance::where('student_id', $studentB->id)->count());
    }

    public function test_wrong_branch_staff_card_is_also_rejected(): void
    {
        $staffB = $this->makeStaff($this->branchB, 'STF-9001');
        $operatorAtBranchA = $this->makeOperator($this->branchA);

        $this->scan($operatorAtBranchA, 'STF-9001')->assertJson(['result' => 'wrong_branch']);
        $this->assertEquals(0, StaffAttendance::where('staff_id', $staffB->id)->count());
    }

    // ── Family notification ──────────────────────────────────────────────────

    public function test_family_notification_dispatched_on_arrival(): void
    {
        Notification::fake();
        [$student, $guardian] = $this->makeStudentWithGuardian($this->branchA, 'KIA-26-0007');
        $operator = $this->makeOperator($this->branchA);

        $this->scan($operator, 'KIA-26-0007');

        Notification::assertSentTo($guardian, StudentGateEvent::class, function ($notification) use ($student) {
            return $notification->student->id === $student->id && $notification->eventType === 'arrival';
        });
    }

    public function test_no_notification_on_duplicate_or_rejected_scans(): void
    {
        Notification::fake();
        [$student, $guardian] = $this->makeStudentWithGuardian($this->branchA, 'KIA-26-0008');
        $operator = $this->makeOperator($this->branchA);

        $this->scan($operator, 'KIA-26-0008'); // arrival — 1 notification
        $this->scan($operator, 'KIA-26-0008'); // duplicate — must NOT notify again

        Notification::assertSentToTimes($guardian, StudentGateEvent::class, 1);
    }

    public function test_departure_notification_when_departure_tracking_enabled(): void
    {
        Notification::fake();
        BranchContext::within($this->branchA->id, fn () => Setting::set('gate_track_departure', '1'));
        [$student, $guardian] = $this->makeStudentWithGuardian($this->branchA, 'KIA-26-0009');
        $operator = $this->makeOperator($this->branchA);

        $this->scan($operator, 'KIA-26-0009'); // arrival
        Carbon::setTestNow(Carbon::now()->addMinutes(5));
        $this->scan($operator, 'KIA-26-0009')->assertJson(['result' => 'matched', 'event' => 'departure']);

        $this->assertNotNull(Attendance::where('student_id', $student->id)->value('departure_time'));
        Notification::assertSentTo($guardian, StudentGateEvent::class, fn ($n) => $n->eventType === 'departure');
    }

    // ── Staff check-in ───────────────────────────────────────────────────────

    public function test_staff_scan_updates_staff_attendance_correctly(): void
    {
        $staff = $this->makeStaff($this->branchA, 'STF-1001');
        $operator = $this->makeOperator($this->branchA);

        Carbon::setTestNow('2026-07-15 07:45:00'); // past default late cutoff

        $this->scan($operator, 'STF-1001')->assertJson(['result' => 'matched', 'event' => 'arrival']);

        $record = StaffAttendance::where('staff_id', $staff->id)->firstOrFail();
        $this->assertEquals('late', $record->status);
        $this->assertEquals('gate_scan', $record->method);
        $this->assertNotNull($record->arrival_time);
    }

    public function test_staff_punctuality_report_shows_correct_counts_on_seeded_data(): void
    {
        $onTimeStaff = $this->makeStaff($this->branchA, 'STF-2001');
        $lateStaff   = $this->makeStaff($this->branchA, 'STF-2002');
        $admin = $this->makeOperator($this->branchA);
        $admin->syncRoles(['admin']);

        BranchContext::within($this->branchA->id, function () use ($onTimeStaff, $lateStaff) {
            StaffAttendance::create(['staff_id' => $onTimeStaff->id, 'date' => '2026-07-01', 'status' => 'present', 'method' => 'gate_scan']);
            StaffAttendance::create(['staff_id' => $onTimeStaff->id, 'date' => '2026-07-02', 'status' => 'present', 'method' => 'gate_scan']);
            StaffAttendance::create(['staff_id' => $lateStaff->id, 'date' => '2026-07-01', 'status' => 'late', 'method' => 'gate_scan']);
        });

        $html = $this->actingAs($admin)
            ->get(route('reports.staff-punctuality', ['month' => '2026-07']))
            ->assertOk()
            ->getContent();

        // On-time staff: 2 on-time, 0 late. Late staff: 0 on-time, 1 late.
        $this->assertMatchesRegularExpression('/STF-2001.*?<td>2<\/td>\s*<td>0<\/td>/s', $html);
        $this->assertMatchesRegularExpression('/STF-2002.*?<td>0<\/td>\s*<td>1<\/td>/s', $html);
    }

    // ── Authorization ────────────────────────────────────────────────────────

    public function test_user_without_gate_scan_permission_cannot_reach_station_page(): void
    {
        $teacher = User::factory()->create(['status' => 'active', 'branch_id' => $this->branchA->id]);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)->get(route('gate.station'))->assertForbidden();
    }

    public function test_user_without_gate_scan_permission_cannot_post_scans(): void
    {
        $teacher = User::factory()->create(['status' => 'active', 'branch_id' => $this->branchA->id]);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)->postJson(route('gate.scan'), ['code' => 'anything'])->assertForbidden();
    }

    public function test_receptionist_can_reach_station_page(): void
    {
        $operator = $this->makeOperator($this->branchA);
        $this->actingAs($operator)->get(route('gate.station'))->assertOk();
    }

    public function test_guest_cannot_reach_station_or_scan_endpoint(): void
    {
        $this->get(route('gate.station'))->assertRedirect(route('login'));
        $this->postJson(route('gate.scan'), ['code' => 'x'])->assertUnauthorized();
    }

    // ── Arrivals feed (dashboard widget) ─────────────────────────────────────

    public function test_arrivals_feed_reflects_todays_scans(): void
    {
        [$student] = $this->makeStudentWithGuardian($this->branchA, 'KIA-26-0010');
        $operator = $this->makeOperator($this->branchA);
        $admin = User::factory()->create(['status' => 'active', 'branch_id' => $this->branchA->id]);
        $admin->assignRole('admin');

        $this->scan($operator, 'KIA-26-0010');

        $this->actingAs($admin)->getJson(route('gate.arrivals-feed'))
            ->assertOk()
            ->assertJsonPath('present', 1)
            ->assertJsonCount(1, 'recent');
    }

    /**
     * Caught live (not by the automated suite, which happened to only test
     * this endpoint with an admin account): receptionist has gate.scan but
     * not analytics.view, yet the widget is embedded on their OWN dashboard
     * — the endpoint must accept either permission, not just analytics.view.
     */
    public function test_receptionist_can_read_their_own_dashboards_arrivals_feed(): void
    {
        $operator = $this->makeOperator($this->branchA);

        $this->actingAs($operator)->getJson(route('gate.arrivals-feed'))->assertOk();
    }

    public function test_user_with_neither_permission_cannot_read_arrivals_feed(): void
    {
        $student = User::factory()->create(['status' => 'active', 'branch_id' => $this->branchA->id]);
        $student->assignRole('student');

        $this->actingAs($student)->getJson(route('gate.arrivals-feed'))->assertForbidden();
    }

    public function test_arrivals_feed_is_branch_scoped(): void
    {
        [$studentA] = $this->makeStudentWithGuardian($this->branchA, 'KIA-26-0011');
        [$studentB] = $this->makeStudentWithGuardian($this->branchB, 'KIA-26-0012');
        $this->scan($this->makeOperator($this->branchA), 'KIA-26-0011');
        $this->scan($this->makeOperator($this->branchB), 'KIA-26-0012');

        $adminA = User::factory()->create(['status' => 'active', 'branch_id' => $this->branchA->id]);
        $adminA->assignRole('admin');

        $this->actingAs($adminA)->getJson(route('gate.arrivals-feed'))
            ->assertOk()
            ->assertJsonPath('present', 1); // only branch A's own scan
    }

    // ── Absentee sweep (scheduled command) ───────────────────────────────────

    public function test_sweep_marks_unscanned_students_absent_after_branch_cutoff_and_alerts(): void
    {
        \Illuminate\Support\Facades\Bus::fake();

        BranchContext::within($this->branchA->id, function () {
            Student::create(['student_code' => 'KIA-26-0013', 'name_en' => 'Never Scanned', 'gender' => 'male', 'status' => 'enrolled'])
                ->sections()->attach($this->section->id, ['academic_year_id' => $this->year->id]);
        });

        Carbon::setTestNow('2026-07-15 09:15:00'); // past the 09:00 default absent cutoff

        $this->artisan('attendance:sweep-gate-absentees')->assertSuccessful();

        $student = Student::where('student_code', 'KIA-26-0013')->firstOrFail();
        $attendance = Attendance::where('student_id', $student->id)->first();

        $this->assertNotNull($attendance);
        $this->assertEquals('absent', $attendance->status);
        \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\SendAbsenceAlerts::class);
    }

    public function test_sweep_does_not_touch_a_student_who_already_scanned(): void
    {
        [$student] = $this->makeStudentWithGuardian($this->branchA, 'KIA-26-0014');
        $this->scan($this->makeOperator($this->branchA), 'KIA-26-0014');

        Carbon::setTestNow('2026-07-15 09:15:00');
        $this->artisan('attendance:sweep-gate-absentees');

        // Still exactly one row (the gate-scan arrival), never overwritten to absent.
        $this->assertEquals(1, Attendance::where('student_id', $student->id)->count());
        $this->assertEquals('present', Attendance::where('student_id', $student->id)->value('status'));
    }

    public function test_sweep_is_idempotent_when_run_twice(): void
    {
        BranchContext::within($this->branchA->id, function () {
            Student::create(['student_code' => 'KIA-26-0015', 'name_en' => 'Sweep Twice', 'gender' => 'male', 'status' => 'enrolled'])
                ->sections()->attach($this->section->id, ['academic_year_id' => $this->year->id]);
        });

        Carbon::setTestNow('2026-07-15 09:15:00');
        $this->artisan('attendance:sweep-gate-absentees')->assertSuccessful();
        $this->artisan('attendance:sweep-gate-absentees')->assertSuccessful();

        $student = Student::where('student_code', 'KIA-26-0015')->firstOrFail();
        $this->assertEquals(1, Attendance::where('student_id', $student->id)->count());
    }
}
