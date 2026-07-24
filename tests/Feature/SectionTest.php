<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\TermResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');
    }

    private function makeSection(string $className = 'Grade 7'): Section
    {
        $class = SchoolClass::create(['name' => $className, 'level' => $className, 'capacity' => 30]);

        return Section::create(['school_class_id' => $class->id, 'name' => 'A']);
    }

    public function test_teacher_cannot_view_the_class_roster_page(): void
    {
        $section = $this->makeSection();
        $teacher = User::factory()->create(['status' => 'active']);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)
            ->get(route('sections.show', $section))
            ->assertForbidden();
    }

    public function test_admin_sees_the_class_roster_with_attendance_average_and_rank(): void
    {
        $year = AcademicYear::create(['name' => 'Y1', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30', 'is_active' => true]);
        $section = $this->makeSection();

        $student = Student::create(['student_code' => 'K-2001', 'name_en' => 'Roster Student', 'gender' => 'male', 'status' => 'enrolled']);
        $student->sections()->attach($section->id, ['academic_year_id' => $year->id]);

        Attendance::create(['student_id' => $student->id, 'section_id' => $section->id, 'date' => today(), 'status' => 'present']);
        TermResult::create([
            'academic_year_id' => $year->id, 'semester' => null, 'student_id' => $student->id, 'section_id' => $section->id,
            'total' => 450, 'average' => 90, 'gpa' => 3.6, 'result' => 'pass', 'rank' => 1, 'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('sections.show', $section));

        $response->assertOk();
        $response->assertSee('Roster Student');
        $response->assertSee('K-2001');
        $response->assertSee('100%'); // 1/1 attendance
        $response->assertSee('90.0'); // average
    }

    public function test_roster_shows_add_student_prompt_when_section_is_empty(): void
    {
        AcademicYear::create(['name' => 'Y1', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30', 'is_active' => true]);
        $section = $this->makeSection();

        $response = $this->actingAs($this->admin)->get(route('sections.show', $section));

        $response->assertOk();
        $response->assertSee('No students enrolled in this section yet.');
    }

    public function test_roster_warns_when_no_active_academic_year_exists(): void
    {
        $section = $this->makeSection();
        // Deliberately no active AcademicYear.

        $response = $this->actingAs($this->admin)->get(route('sections.show', $section));

        $response->assertOk();
        $response->assertSee('No active academic year is set — enrollment cannot be shown.');
    }

    public function test_principal_can_view_the_class_roster_page(): void
    {
        $section = $this->makeSection();
        $principal = User::factory()->create(['status' => 'active']);
        $principal->assignRole('principal');

        $this->actingAs($principal)
            ->get(route('sections.show', $section))
            ->assertOk();
    }
}
