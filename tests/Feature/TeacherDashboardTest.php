<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ClassSubject;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Timetable;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TeacherDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Cache::flush();
    }

    private function makeTeacher(): array
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('teacher');
        $staff = Staff::create(['user_id' => $user->id, 'staff_code' => 'STF-' . uniqid(), 'position' => 'Teacher']);
        return [$user, $staff];
    }

    private function makeSection(?Staff $classTeacher = null): Section
    {
        $class = SchoolClass::create(['name' => 'Grade ' . rand(1, 999)]);
        return Section::create([
            'school_class_id' => $class->id, 'name' => 'A', 'class_teacher_id' => $classTeacher?->id,
        ]);
    }

    private function makeStudent(Section $section, AcademicYear $year): Student
    {
        $student = Student::create([
            'student_code' => 'S-' . uniqid(), 'name_en' => 'Student-' . uniqid(),
            'gender' => 'male', 'status' => 'enrolled',
        ]);
        $section->students()->attach($student->id, ['academic_year_id' => $year->id]);
        return $student;
    }

    private function makeYear(): AcademicYear
    {
        return AcademicYear::create([
            'name' => 'Test Year', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);
    }

    // ── My sections + student counts ────────────────────────────────────

    public function test_dashboard_shows_own_homeroom_section_with_student_count(): void
    {
        [$user, $staff] = $this->makeTeacher();
        $year    = $this->makeYear();
        $section = $this->makeSection($staff);
        $this->makeStudent($section, $year);
        $this->makeStudent($section, $year);

        $this->actingAs($user)
            ->get(route('dashboard.teacher'))
            ->assertOk()
            ->assertSee($section->schoolClass->name . ' A')
            ->assertSee(__('staff_dashboard.students_count', ['count' => 2]));
    }

    public function test_dashboard_shows_section_taught_via_class_subject_even_without_being_class_teacher(): void
    {
        [$user, $staff] = $this->makeTeacher();
        $section = $this->makeSection(); // no class teacher assigned
        $subject = Subject::create(['name_en' => 'Math', 'name_km' => 'M', 'code' => 'M-' . uniqid(), 'full_mark' => 100]);
        ClassSubject::create(['school_class_id' => $section->school_class_id, 'subject_id' => $subject->id, 'teacher_id' => $staff->id]);

        $this->actingAs($user)
            ->get(route('dashboard.teacher'))
            ->assertOk()
            ->assertSee($section->schoolClass->name . ' A');
    }

    // ── Critical: teacher isolation ─────────────────────────────────────

    public function test_teacher_sees_only_own_sections_never_another_teachers(): void
    {
        [$teacherA, $staffA] = $this->makeTeacher();
        [, $staffB] = $this->makeTeacher();

        $sectionA = $this->makeSection($staffA);
        $sectionB = $this->makeSection($staffB);

        $html = $this->actingAs($teacherA)
            ->get(route('dashboard.teacher'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($sectionA->schoolClass->name . ' A', $html);
        $this->assertStringNotContainsString($sectionB->schoolClass->name . ' A', $html);
    }

    // ── Attendance status per section ───────────────────────────────────

    public function test_dashboard_shows_marked_badge_when_attendance_already_taken_today(): void
    {
        [$user, $staff] = $this->makeTeacher();
        $year    = $this->makeYear();
        $section = $this->makeSection($staff);
        $student = $this->makeStudent($section, $year);

        Attendance::create([
            'student_id' => $student->id, 'section_id' => $section->id,
            'date' => now()->toDateString(), 'status' => 'present', 'marked_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.teacher'))
            ->assertOk()
            ->assertSee(__('staff_dashboard.attendance_marked'));
    }

    public function test_dashboard_shows_mark_now_link_when_attendance_not_yet_taken_today(): void
    {
        [$user, $staff] = $this->makeTeacher();
        $section = $this->makeSection($staff);

        $this->actingAs($user)
            ->get(route('dashboard.teacher'))
            ->assertOk()
            ->assertSee(route('attendance.mark', $section), false);
    }

    // ── Today's timetable ────────────────────────────────────────────────

    public function test_dashboard_shows_todays_timetable_slot_for_teacher(): void
    {
        [$user, $staff] = $this->makeTeacher();
        $section = $this->makeSection($staff);
        $subject = Subject::create(['name_en' => 'Science', 'name_km' => 'S', 'code' => 'SCI-' . uniqid(), 'full_mark' => 100]);

        Timetable::create([
            'section_id' => $section->id, 'subject_id' => $subject->id, 'teacher_id' => $staff->id,
            'day' => strtolower(now()->format('l')), 'period' => 1,
            'start_time' => '07:00', 'end_time' => '08:00',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.teacher'))
            ->assertOk()
            ->assertSee('Science');
    }

    public function test_dashboard_does_not_show_tomorrows_timetable_slot(): void
    {
        [$user, $staff] = $this->makeTeacher();
        $section = $this->makeSection($staff);
        $subject = Subject::create(['name_en' => 'Geography', 'name_km' => 'G', 'code' => 'GEO-' . uniqid(), 'full_mark' => 100]);

        $tomorrow = strtolower(now()->addDay()->format('l'));
        Timetable::create([
            'section_id' => $section->id, 'subject_id' => $subject->id, 'teacher_id' => $staff->id,
            'day' => $tomorrow, 'period' => 1, 'start_time' => '07:00', 'end_time' => '08:00',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.teacher'))
            ->assertOk()
            ->assertDontSee('Geography');
    }

    // ── Pending homework to grade ───────────────────────────────────────

    public function test_dashboard_shows_correct_pending_grade_count(): void
    {
        [$user, $staff] = $this->makeTeacher();
        $year    = $this->makeYear();
        $section = $this->makeSection($staff);
        $subject = Subject::create(['name_en' => 'Math', 'name_km' => 'M', 'code' => 'M-' . uniqid(), 'full_mark' => 100]);

        $homework = Homework::create([
            'section_id' => $section->id, 'subject_id' => $subject->id, 'teacher_id' => $staff->id,
            'title' => 'HW1', 'due_date' => now()->addWeek(), 'published_at' => now(),
        ]);

        $s1 = $this->makeStudent($section, $year);
        $s2 = $this->makeStudent($section, $year);
        $s3 = $this->makeStudent($section, $year);

        // 2 ungraded, 1 graded.
        HomeworkSubmission::create(['homework_id' => $homework->id, 'student_id' => $s1->id, 'submitted_at' => now(), 'is_late' => false]);
        HomeworkSubmission::create(['homework_id' => $homework->id, 'student_id' => $s2->id, 'submitted_at' => now(), 'is_late' => false]);
        HomeworkSubmission::create(['homework_id' => $homework->id, 'student_id' => $s3->id, 'submitted_at' => now(), 'is_late' => false, 'grade' => 85]);

        $html = $this->actingAs($user)
            ->get(route('dashboard.teacher'))
            ->assertOk()
            ->getContent();

        // Whitespace-normalized: the count sits on its own indented line in the template.
        $this->assertStringContainsString('> 2 <', preg_replace('/\s+/', ' ', $html));
    }

    public function test_pending_grade_count_excludes_other_teachers_homework(): void
    {
        [$userA, $staffA] = $this->makeTeacher();
        [, $staffB] = $this->makeTeacher();
        $year     = $this->makeYear();
        $sectionB = $this->makeSection($staffB);
        $subject  = Subject::create(['name_en' => 'Math', 'name_km' => 'M', 'code' => 'M-' . uniqid(), 'full_mark' => 100]);

        $homeworkB = Homework::create([
            'section_id' => $sectionB->id, 'subject_id' => $subject->id, 'teacher_id' => $staffB->id,
            'title' => 'HW-B', 'due_date' => now()->addWeek(), 'published_at' => now(),
        ]);
        $student = $this->makeStudent($sectionB, $year);
        HomeworkSubmission::create(['homework_id' => $homeworkB->id, 'student_id' => $student->id, 'submitted_at' => now(), 'is_late' => false]);

        // Teacher A has no sections/homework at all — must show 0, not teacher B's 1.
        $html = $this->actingAs($userA)
            ->get(route('dashboard.teacher'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('> 0 <', preg_replace('/\s+/', ' ', $html));
    }
}
