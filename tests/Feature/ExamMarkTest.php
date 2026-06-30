<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamMarkTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected User $admin;
    protected Exam $exam;
    protected Section $section;
    protected Subject $subject;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');

        $this->teacher = User::factory()->create(['status' => 'active']);
        $this->teacher->assignRole('teacher');

        $year = AcademicYear::create(['name' => 'Y1', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30', 'is_active' => true]);
        $class = SchoolClass::create(['name' => 'Grade 10', 'level' => 'High', 'capacity' => 30]);
        $this->section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);
        $this->subject = Subject::create(['name_en' => 'Math', 'name_km' => 'M', 'code' => 'M1', 'full_mark' => 100, 'coefficient' => 1]);

        $sUser = User::factory()->create(['status' => 'active']);
        $this->student = Student::create(['user_id' => $sUser->id, 'student_code' => 'K-0001', 'name_en' => 'Alice', 'name_km' => 'ស', 'gender' => 'female', 'status' => 'enrolled']);

        \DB::table('student_section')->insert([
            'student_id' => $this->student->id, 'section_id' => $this->section->id,
            'academic_year_id' => $year->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        \DB::table('class_subject')->insert([
            'school_class_id' => $class->id, 'subject_id' => $this->subject->id,
        ]);

        $this->exam = Exam::create(['academic_year_id' => $year->id, 'name' => 'Midterm', 'type' => 'midterm', 'is_published' => false]);
    }

    public function test_teacher_can_access_mark_grid(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('exam-marks.grid', [$this->exam, $this->section]));

        $response->assertOk();
    }

    public function test_teacher_can_save_marks(): void
    {
        $response = $this->actingAs($this->teacher)
            ->post(route('exam-marks.save', [$this->exam, $this->section]), [
                'marks' => [$this->student->id => [$this->subject->id => 85]],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('exam_marks', [
            'exam_id'    => $this->exam->id,
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'score'      => 85,
        ]);
    }

    public function test_marks_locked_after_publish(): void
    {
        $this->exam->update(['is_published' => true]);

        $response = $this->actingAs($this->teacher)
            ->post(route('exam-marks.save', [$this->exam, $this->section]), [
                'marks' => [$this->student->id => [$this->subject->id => 75]],
            ]);

        $response->assertRedirect(); // redirects back with error
        $this->assertDatabaseMissing('exam_marks', ['exam_id' => $this->exam->id, 'score' => 75]);
    }

    public function test_admin_can_bypass_and_view_marks(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('exam-marks.grid', [$this->exam, $this->section]));

        $response->assertOk();
    }

    public function test_student_cannot_access_mark_grid(): void
    {
        $sUser = User::factory()->create(['status' => 'active']);
        $sUser->assignRole('student');

        $response = $this->actingAs($sUser)
            ->get(route('exam-marks.grid', [$this->exam, $this->section]));

        $response->assertForbidden();
    }
}
