<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\GradeScale;
use App\Models\Section;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\GradingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradingTest extends TestCase
{
    use RefreshDatabase;

    protected GradingService $grading;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'GradeScaleSeeder']);
        $this->grading = new GradingService();
    }

    // --- gradeFor boundary tests ---

    public function test_score_100_is_grade_A(): void
    {
        $grade = $this->grading->gradeFor(100);
        $this->assertEquals('A', $grade->grade);
    }

    public function test_score_90_is_grade_A(): void
    {
        $grade = $this->grading->gradeFor(90);
        $this->assertEquals('A', $grade->grade);
    }

    public function test_score_89_is_grade_B(): void
    {
        $grade = $this->grading->gradeFor(89);
        $this->assertEquals('B', $grade->grade);
    }

    public function test_score_50_is_grade_D(): void
    {
        $grade = $this->grading->gradeFor(50);
        $this->assertEquals('D', $grade->grade);
    }

    public function test_score_49_is_grade_F(): void
    {
        $grade = $this->grading->gradeFor(49);
        $this->assertEquals('F', $grade->grade);
    }

    public function test_score_0_is_grade_F(): void
    {
        $grade = $this->grading->gradeFor(0);
        $this->assertEquals('F', $grade->grade);
    }

    // --- computeResults ---

    private function makeExamWithStudents(array $studentScores): Exam
    {
        $year    = AcademicYear::create(['name' => 'Test Year', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30', 'is_active' => true]);
        $exam    = Exam::create(['academic_year_id' => $year->id, 'name' => 'Test Exam', 'type' => 'midterm', 'is_published' => false]);
        $subject = Subject::create(['name_en' => 'Math', 'name_km' => 'គណិត', 'code' => 'M01', 'full_mark' => 100, 'coefficient' => 1]);

        foreach ($studentScores as $score) {
            $user    = User::factory()->create(['status' => 'active']);
            $student = Student::create(['user_id' => $user->id, 'student_code' => 'K-' . rand(1000,9999), 'name_en' => 'Student', 'name_km' => 'សិស្ស', 'gender' => 'male', 'status' => 'enrolled']);
            ExamMark::create(['exam_id' => $exam->id, 'student_id' => $student->id, 'subject_id' => $subject->id, 'score' => $score]);
        }

        return $exam;
    }

    public function test_weighted_average_computed_correctly(): void
    {
        $year    = AcademicYear::create(['name' => 'Test Year', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30', 'is_active' => true]);
        $exam    = Exam::create(['academic_year_id' => $year->id, 'name' => 'Test', 'type' => 'final', 'is_published' => false]);
        $math    = Subject::create(['name_en' => 'Math',    'name_km' => 'M', 'code' => 'M01', 'full_mark' => 100, 'coefficient' => 2]);
        $english = Subject::create(['name_en' => 'English', 'name_km' => 'E', 'code' => 'E01', 'full_mark' => 100, 'coefficient' => 1]);

        $user    = User::factory()->create(['status' => 'active']);
        $student = Student::create(['user_id' => $user->id, 'student_code' => 'K-0001', 'name_en' => 'Alice', 'name_km' => 'ស', 'gender' => 'female', 'status' => 'enrolled']);

        // Math coef=2 score=90, English coef=1 score=60 → avg = (90*2 + 60*1) / 3 = 240/3 = 80
        ExamMark::create(['exam_id' => $exam->id, 'student_id' => $student->id, 'subject_id' => $math->id,    'score' => 90]);
        ExamMark::create(['exam_id' => $exam->id, 'student_id' => $student->id, 'subject_id' => $english->id, 'score' => 60]);

        $this->grading->computeResults($exam);

        $result = $exam->results()->where('student_id', $student->id)->first();
        $this->assertEquals(80.0, (float) $result->average);
        $this->assertEquals('pass', $result->result);
    }

    public function test_tied_rank_uses_standard_competition_ranking(): void
    {
        $exam = $this->makeExamWithStudents([90, 90, 70]);
        $this->grading->computeResults($exam);

        $results = $exam->results()->orderByDesc('average')->get();
        $this->assertEquals(1, $results[0]->rank);
        $this->assertEquals(1, $results[1]->rank);
        $this->assertEquals(3, $results[2]->rank); // skips rank 2
    }

    public function test_student_below_passmark_is_fail(): void
    {
        $exam = $this->makeExamWithStudents([45]);
        $this->grading->computeResults($exam);

        $result = $exam->results()->first();
        $this->assertEquals('fail', $result->result);
    }

    public function test_student_at_passmark_is_pass(): void
    {
        $exam = $this->makeExamWithStudents([50]);
        $this->grading->computeResults($exam);

        $result = $exam->results()->first();
        $this->assertEquals('pass', $result->result);
    }
}
