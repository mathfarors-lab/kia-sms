<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\Holiday;
use App\Models\Semester;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicPlanningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }

    // ── Semester Planning ────────────────────────────────────────────────────

    public function test_principal_can_add_a_semester_within_the_year_range(): void
    {
        $year = AcademicYear::create(['name' => '2026-2027', 'start_date' => '2026-08-01', 'end_date' => '2027-05-31']);

        $response = $this->actingAs($this->makeUser('principal'))
            ->post(route('semesters.store', $year), [
                'semester_number' => 1, 'name' => 'Semester 1',
                'start_date' => '2026-08-01', 'end_date' => '2026-12-20',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('semesters', ['academic_year_id' => $year->id, 'semester_number' => 1]);
    }

    public function test_semester_dates_outside_the_academic_year_range_are_rejected(): void
    {
        $year = AcademicYear::create(['name' => '2026-2027', 'start_date' => '2026-08-01', 'end_date' => '2027-05-31']);

        $this->actingAs($this->makeUser('principal'))
            ->post(route('semesters.store', $year), [
                'semester_number' => 1,
                'start_date' => '2026-01-01', 'end_date' => '2026-12-20',
            ])
            ->assertSessionHasErrors('start_date');

        $this->assertDatabaseMissing('semesters', ['academic_year_id' => $year->id]);
    }

    public function test_duplicate_semester_number_in_the_same_year_is_rejected(): void
    {
        $year = AcademicYear::create(['name' => '2026-2027', 'start_date' => '2026-08-01', 'end_date' => '2027-05-31']);
        Semester::create(['academic_year_id' => $year->id, 'semester_number' => 1, 'start_date' => '2026-08-01', 'end_date' => '2026-12-20']);

        $this->actingAs($this->makeUser('principal'))
            ->post(route('semesters.store', $year), [
                'semester_number' => 1,
                'start_date' => '2027-01-05', 'end_date' => '2027-05-31',
            ])
            ->assertSessionHasErrors('semester_number');
    }

    public function test_teacher_cannot_add_a_semester(): void
    {
        $year = AcademicYear::create(['name' => '2026-2027', 'start_date' => '2026-08-01', 'end_date' => '2027-05-31']);

        $this->actingAs($this->makeUser('teacher'))
            ->post(route('semesters.store', $year), [
                'semester_number' => 1, 'start_date' => '2026-08-01', 'end_date' => '2026-12-20',
            ])
            ->assertForbidden();
    }

    public function test_principal_can_delete_a_semester(): void
    {
        $year = AcademicYear::create(['name' => '2026-2027', 'start_date' => '2026-08-01', 'end_date' => '2027-05-31']);
        $semester = Semester::create(['academic_year_id' => $year->id, 'semester_number' => 1, 'start_date' => '2026-08-01', 'end_date' => '2026-12-20']);

        $this->actingAs($this->makeUser('principal'))
            ->delete(route('semesters.destroy', $semester))
            ->assertRedirect();

        $this->assertDatabaseMissing('semesters', ['id' => $semester->id]);
    }

    // ── Holidays ─────────────────────────────────────────────────────────────

    public function test_principal_can_add_a_holiday(): void
    {
        $response = $this->actingAs($this->makeUser('principal'))->post(route('holidays.store'), [
            'name' => 'Khmer New Year', 'start_date' => '2026-04-14', 'end_date' => '2026-04-16',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('holidays', ['name' => 'Khmer New Year']);
    }

    public function test_teacher_cannot_manage_holidays(): void
    {
        $this->actingAs($this->makeUser('teacher'))
            ->get(route('holidays.index'))
            ->assertForbidden();

        $this->actingAs($this->makeUser('teacher'))->post(route('holidays.store'), [
            'name' => 'Khmer New Year', 'start_date' => '2026-04-14', 'end_date' => '2026-04-16',
        ])->assertForbidden();
    }

    public function test_holiday_end_date_before_start_date_is_rejected(): void
    {
        $this->actingAs($this->makeUser('principal'))->post(route('holidays.store'), [
            'name' => 'Bad Holiday', 'start_date' => '2026-04-16', 'end_date' => '2026-04-14',
        ])->assertSessionHasErrors('end_date');
    }

    public function test_principal_can_delete_a_holiday(): void
    {
        $holiday = Holiday::create(['name' => 'Water Festival', 'start_date' => '2026-11-01', 'end_date' => '2026-11-03']);

        $this->actingAs($this->makeUser('principal'))
            ->delete(route('holidays.destroy', $holiday))
            ->assertRedirect();

        $this->assertDatabaseMissing('holidays', ['id' => $holiday->id]);
    }

    // ── Academic Calendar — read-only aggregation, open to any authenticated user ──

    public function test_any_authenticated_role_can_view_the_calendar_without_a_permission(): void
    {
        $this->actingAs($this->makeUser('librarian'))
            ->get(route('academic-calendar.index'))
            ->assertOk();
    }

    public function test_calendar_aggregates_holidays_exams_and_semester_boundaries_for_the_requested_month(): void
    {
        $year = AcademicYear::create(['name' => '2026-2027', 'start_date' => '2026-08-01', 'end_date' => '2027-05-31']);
        Semester::create(['academic_year_id' => $year->id, 'semester_number' => 1, 'start_date' => '2026-08-01', 'end_date' => '2026-08-15']);
        Holiday::create(['name' => 'Test Holiday', 'start_date' => '2026-08-17', 'end_date' => '2026-08-19']);
        Exam::create([
            'academic_year_id' => $year->id, 'name' => 'August Quiz', 'type' => 'monthly',
            'semester' => 1, 'exam_date' => '2026-08-10', 'weight' => 1,
        ]);
        // Outside the requested month — must not leak into it.
        Exam::create([
            'academic_year_id' => $year->id, 'name' => 'September Quiz', 'type' => 'monthly',
            'semester' => 1, 'exam_date' => '2026-09-05', 'weight' => 1,
        ]);

        $response = $this->actingAs($this->makeUser('admin'))
            ->get(route('academic-calendar.index', ['month' => '2026-08']));

        $response->assertOk();
        $events = $response->viewData('monthEvents');

        $this->assertTrue($events->contains(fn ($e) => $e['date'] === '2026-08-10' && $e['type'] === 'exam'));
        $this->assertTrue($events->contains(fn ($e) => $e['date'] === '2026-08-17' && $e['type'] === 'holiday'));
        $this->assertTrue($events->contains(fn ($e) => $e['date'] === '2026-08-18' && $e['type'] === 'holiday'));
        $this->assertTrue($events->contains(fn ($e) => $e['date'] === '2026-08-19' && $e['type'] === 'holiday'));
        $this->assertTrue($events->contains(fn ($e) => $e['date'] === '2026-08-01' && $e['type'] === 'semester'));
        $this->assertTrue($events->contains(fn ($e) => $e['date'] === '2026-08-15' && $e['type'] === 'semester'));
        $this->assertFalse($events->contains(fn ($e) => $e['date'] === '2026-09-05'));
    }

    public function test_month_navigation_links_compute_the_adjacent_months(): void
    {
        $response = $this->actingAs($this->makeUser('admin'))
            ->get(route('academic-calendar.index', ['month' => '2026-08']));

        $this->assertEquals('2026-07', $response->viewData('prevMonth'));
        $this->assertEquals('2026-09', $response->viewData('nextMonth'));
    }

    public function test_december_next_month_rolls_into_the_following_year(): void
    {
        $response = $this->actingAs($this->makeUser('admin'))
            ->get(route('academic-calendar.index', ['month' => '2026-12']));

        $this->assertEquals('2026-11', $response->viewData('prevMonth'));
        $this->assertEquals('2027-01', $response->viewData('nextMonth'));
    }
}
