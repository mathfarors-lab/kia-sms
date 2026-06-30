<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\ExamResult;
use App\Models\GradeScale;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportCardTest extends TestCase
{
    use RefreshDatabase;

    protected Exam $exam;
    protected Student $student;
    protected User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'GradeScaleSeeder']);

        $year = AcademicYear::create(['name' => 'Y', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30', 'is_active' => true]);
        $this->exam = Exam::create(['academic_year_id' => $year->id, 'name' => 'Final', 'type' => 'final', 'is_published' => true]);

        $this->studentUser = User::factory()->create(['status' => 'active']);
        $this->studentUser->assignRole('student');

        $this->student = Student::create([
            'user_id' => $this->studentUser->id, 'student_code' => 'K-0001',
            'name_en' => 'Alice', 'name_km' => 'ស', 'gender' => 'female', 'status' => 'enrolled',
        ]);

        $subject = Subject::create(['name_en' => 'Math', 'name_km' => 'M', 'code' => 'M1', 'full_mark' => 100, 'coefficient' => 1]);
        ExamMark::create(['exam_id' => $this->exam->id, 'student_id' => $this->student->id, 'subject_id' => $subject->id, 'score' => 85, 'grade' => 'B']);
        ExamResult::create(['exam_id' => $this->exam->id, 'student_id' => $this->student->id, 'total' => 85, 'average' => 85, 'gpa' => 3.0, 'rank' => 1, 'result' => 'pass']);
    }

    public function test_student_can_view_own_report_card(): void
    {
        $response = $this->actingAs($this->studentUser)
            ->get(route('report-card.show', [$this->exam, $this->student]));

        $response->assertOk();
        $response->assertSee('Alice');
    }

    public function test_student_cannot_view_other_student_report_card(): void
    {
        $other = User::factory()->create(['status' => 'active']);
        $other->assignRole('student');
        $otherStudent = Student::create(['user_id' => $other->id, 'student_code' => 'K-0002', 'name_en' => 'Bob', 'name_km' => 'ប', 'gender' => 'male', 'status' => 'enrolled']);

        $response = $this->actingAs($this->studentUser)
            ->get(route('report-card.show', [$this->exam, $otherStudent]));

        $response->assertForbidden();
    }

    public function test_unpublished_exam_blocks_report_card(): void
    {
        $this->exam->update(['is_published' => false]);

        $response = $this->actingAs($this->studentUser)
            ->get(route('report-card.show', [$this->exam, $this->student]));

        $response->assertForbidden();
    }

    public function test_admin_can_view_any_student_report_card(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->get(route('report-card.show', [$this->exam, $this->student]));

        $response->assertOk();
    }

    public function test_parent_can_view_ward_report_card(): void
    {
        $parentUser = User::factory()->create(['status' => 'active']);
        $parentUser->assignRole('parent');

        \DB::table('student_guardian')->insert([
            'student_id'  => $this->student->id,
            'guardian_id' => $parentUser->id,
            'relation'    => 'parent',
            'is_primary'  => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $response = $this->actingAs($parentUser)
            ->get(route('report-card.show', [$this->exam, $this->student]));

        $response->assertOk();
    }
}
