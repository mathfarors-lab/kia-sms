<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\Section;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeStudentUser(): array
    {
        $uid  = uniqid();
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('student');
        $student = Student::create([
            'user_id'      => $user->id,
            'name_en'      => 'Student-' . $uid,
            'name_km'      => null,
            'student_code' => 'S-' . $uid,
            'gender'       => 'female',
            'status'       => 'enrolled',
        ]);
        return [$user, $student];
    }

    // ── Happy path ─────────────────────────────────────────────────────────────

    public function test_student_sees_own_attendance(): void
    {
        [$user, $student] = $this->makeStudentUser();

        $class   = SchoolClass::create(['name' => 'Grade 1']);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);

        Attendance::create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'date'       => '2026-06-01',
            'status'     => 'present',
        ]);
        Attendance::create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'date'       => '2026-06-02',
            'status'     => 'absent',
        ]);

        $this->actingAs($user)
             ->get(route('student.attendance'))
             ->assertOk()
             ->assertSee('50%'); // 1 present out of 2 days
    }

    public function test_student_attendance_page_shows_monthly_breakdown(): void
    {
        [$user, $student] = $this->makeStudentUser();

        $class   = SchoolClass::create(['name' => 'Grade 2']);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'B']);

        Attendance::create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'date'       => '2026-05-15',
            'status'     => 'present',
        ]);

        $this->actingAs($user)
             ->get(route('student.attendance'))
             ->assertOk()
             ->assertSee('May 2026');
    }

    // ── IDOR guard — student sees only own data ────────────────────────────────

    public function test_student_portal_scoped_to_authenticated_student(): void
    {
        [$user1, $student1] = $this->makeStudentUser();
        [$user2, $student2] = $this->makeStudentUser();

        $class   = SchoolClass::create(['name' => 'Grade 3']);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'C']);

        // Attendance for student2 only
        Attendance::create([
            'student_id' => $student2->id,
            'section_id' => $section->id,
            'date'       => '2026-06-10',
            'status'     => 'absent',
        ]);

        // user1 should see their own data (zero records), NOT student2's record
        $this->actingAs($user1)
             ->get(route('student.attendance'))
             ->assertOk()
             ->assertSee($student1->name_en)
             ->assertDontSee($student2->name_en);
    }

    // ── Non-student cannot access portal ──────────────────────────────────────

    public function test_non_student_cannot_access_attendance_portal(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('parent');

        $this->actingAs($user)
             ->get(route('student.attendance'))
             ->assertForbidden();
    }

    // ── Published exams only shown ──────────────────────────────────────────────

    public function test_only_published_exams_appear_as_quick_links(): void
    {
        [$user, $student] = $this->makeStudentUser();

        $year = AcademicYear::create([
            'name' => 'FY2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);
        Exam::create(['academic_year_id' => $year->id, 'name' => 'Final Exam', 'type' => 'final', 'is_published' => true]);
        Exam::create(['academic_year_id' => $year->id, 'name' => 'Hidden Exam', 'type' => 'midterm', 'is_published' => false]);

        $this->actingAs($user)
             ->get(route('student.attendance'))
             ->assertOk()
             ->assertSee('Final Exam')
             ->assertDontSee('Hidden Exam');
    }
}
