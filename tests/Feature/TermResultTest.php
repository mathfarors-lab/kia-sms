<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\GradeScale;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TermResult;
use App\Models\User;
use App\Services\TermGradingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TermResultTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        // Grade scale: 0-49 → F (GPA 0), 50-69 → C (GPA 2), 70-89 → B (GPA 3), 90-100 → A (GPA 4)
        GradeScale::insert([
            ['grade' => 'F', 'min_score' =>  0, 'max_score' => 49, 'gpa' => 0.00],
            ['grade' => 'C', 'min_score' => 50, 'max_score' => 69, 'gpa' => 2.00],
            ['grade' => 'B', 'min_score' => 70, 'max_score' => 89, 'gpa' => 3.00],
            ['grade' => 'A', 'min_score' => 90, 'max_score' => 100, 'gpa' => 4.00],
        ]);

        Setting::set('pass_mark', 50, 'grading');
        Setting::set('missing_mark_policy', 'exclude-and-flag', 'grading');
    }

    // ── Shared setup helpers ─────────────────────────────────────────────────────

    private function makeYear(): AcademicYear
    {
        return AcademicYear::create([
            'name' => 'Test Year', 'start_date' => '2026-01-01',
            'end_date' => '2026-12-31', 'is_active' => true,
        ]);
    }

    private function makeSection(AcademicYear $year): array
    {
        $class   = SchoolClass::create(['name' => 'Grade 10']);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);
        return [$class, $section];
    }

    private function makeStudent(Section $section, AcademicYear $year): Student
    {
        $uid  = uniqid();
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('student');
        $student = Student::create([
            'user_id'      => $user->id,
            'name_en'      => 'Student-' . $uid,
            'name_km'      => null,
            'student_code' => 'S-' . $uid,
            'gender'       => 'male',
            'status'       => 'enrolled',
        ]);
        $section->students()->attach($student->id, ['academic_year_id' => $year->id]);
        return $student;
    }

    private function makeSubject(float $coef = 1.0): Subject
    {
        return Subject::create([
            'name_en'     => 'Subject-' . uniqid(),
            'name_km'     => null,
            'code'        => 'SUB-' . uniqid(),
            'coefficient' => $coef,
            'full_mark'   => 100,
        ]);
    }

    private function makeExam(AcademicYear $year, int $semester, float $weight = 1.0, bool $published = true): Exam
    {
        return Exam::create([
            'academic_year_id' => $year->id,
            'name'             => 'Exam-' . uniqid(),
            'type'             => 'monthly',
            'semester'         => $semester,
            'weight'           => $weight,
            'is_published'     => $published,
        ]);
    }

    private function mark(Exam $exam, Student $student, Subject $subject, float $score): void
    {
        ExamMark::create([
            'exam_id'    => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'score'      => $score,
            'grade'      => null,
        ]);
    }

    private function service(): TermGradingService
    {
        return app(TermGradingService::class);
    }

    // ── Test: weighted consolidation math ────────────────────────────────────────

    public function test_weighted_consolidation_math_is_correct(): void
    {
        $year              = $this->makeYear();
        [, $section]       = $this->makeSection($year);
        $student           = $this->makeStudent($section, $year);
        $subject           = $this->makeSubject(1.0); // coef=1

        // Exam A (weight=2): score=80 → contribution 80*2*1 = 160, coef-weight 2*1 = 2
        $examA = $this->makeExam($year, 1, weight: 2.0);
        // Exam B (weight=1): score=60 → contribution 60*1*1 = 60, coef-weight 1*1 = 1
        $examB = $this->makeExam($year, 1, weight: 1.0);

        $this->mark($examA, $student, $subject, 80);
        $this->mark($examB, $student, $subject, 60);

        // Expected average = (160 + 60) / (2 + 1) = 220 / 3 ≈ 73.33
        $this->service()->compute($year, 1);

        $tr = TermResult::where(['academic_year_id' => $year->id, 'semester' => 1, 'student_id' => $student->id])->firstOrFail();
        $this->assertEquals(73.33, $tr->average);
    }

    // ── Test: subject coefficient applied ────────────────────────────────────────

    public function test_coefficient_applied_in_consolidation(): void
    {
        $year        = $this->makeYear();
        [, $section] = $this->makeSection($year);
        $student     = $this->makeStudent($section, $year);

        $subjectX = $this->makeSubject(2.0); // coef=2
        $subjectY = $this->makeSubject(1.0); // coef=1

        $exam = $this->makeExam($year, 1, weight: 1.0);
        $this->mark($exam, $student, $subjectX, 80); // 80*1*2=160, weight 1*2=2
        $this->mark($exam, $student, $subjectY, 40); // 40*1*1=40,  weight 1*1=1

        // average = (160+40)/(2+1) = 200/3 ≈ 66.67
        $this->service()->compute($year, 1);

        $tr = TermResult::where(['academic_year_id' => $year->id, 'semester' => 1, 'student_id' => $student->id])->firstOrFail();
        $this->assertEquals(66.67, $tr->average);
    }

    // ── Test: ranking with ties share rank ──────────────────────────────────────

    public function test_ranking_ties_share_rank(): void
    {
        $year        = $this->makeYear();
        [, $section] = $this->makeSection($year);
        $s1 = $this->makeStudent($section, $year);
        $s2 = $this->makeStudent($section, $year);
        $s3 = $this->makeStudent($section, $year);

        $subject = $this->makeSubject(1.0);
        $exam    = $this->makeExam($year, 1);

        $this->mark($exam, $s1, $subject, 90); // rank 1
        $this->mark($exam, $s2, $subject, 80); // rank 2 (tied)
        $this->mark($exam, $s3, $subject, 80); // rank 2 (tied)

        $this->service()->compute($year, 1);

        $r1 = TermResult::where(['semester' => 1, 'student_id' => $s1->id])->value('rank');
        $r2 = TermResult::where(['semester' => 1, 'student_id' => $s2->id])->value('rank');
        $r3 = TermResult::where(['semester' => 1, 'student_id' => $s3->id])->value('rank');

        $this->assertEquals(1, $r1);
        $this->assertEquals(2, $r2);
        $this->assertEquals(2, $r3); // tied at rank 2
    }

    // ── Test: missing mark — exclude-and-flag ────────────────────────────────────

    public function test_missing_mark_exclude_and_flag(): void
    {
        Setting::set('missing_mark_policy', 'exclude-and-flag', 'grading');

        $year        = $this->makeYear();
        [, $section] = $this->makeSection($year);
        $student     = $this->makeStudent($section, $year);
        $subject     = $this->makeSubject(1.0);

        $examA = $this->makeExam($year, 1, weight: 1.0); // student has marks
        $examB = $this->makeExam($year, 1, weight: 1.0); // student has NO marks → flagged

        $this->mark($examA, $student, $subject, 80);
        // No mark for $examB

        $this->service()->compute($year, 1);

        $tr = TermResult::where(['semester' => 1, 'student_id' => $student->id])->firstOrFail();
        // Average based on examA only → 80; has_missing_marks = true
        $this->assertEquals(80.00, $tr->average);
        $this->assertTrue($tr->has_missing_marks);
    }

    // ── Test: missing mark — treat-as-zero ──────────────────────────────────────

    public function test_missing_mark_treat_as_zero(): void
    {
        Setting::set('missing_mark_policy', 'treat-missing-as-zero', 'grading');

        $year        = $this->makeYear();
        [, $section] = $this->makeSection($year);
        $s1 = $this->makeStudent($section, $year);
        $s2 = $this->makeStudent($section, $year); // needed to define the subject set for the exam

        $subject = $this->makeSubject(1.0);

        $examA = $this->makeExam($year, 1, weight: 1.0);
        $examB = $this->makeExam($year, 1, weight: 1.0);

        $this->mark($examA, $s1, $subject, 80); // s1 has mark for examA
        $this->mark($examB, $s2, $subject, 60); // s2 has mark for examB (defines subject set)
        // s1 has NO mark for examB → treated as 0

        $this->service()->compute($year, 1);

        $tr = TermResult::where(['semester' => 1, 'student_id' => $s1->id])->firstOrFail();
        // contribution examA: 80*1*1=80, weight=1
        // contribution examB missing: 0, weight added=1  → total_weight=2
        // average = (80+0)/2 = 40
        $this->assertEquals(40.00, $tr->average);
        $this->assertTrue($tr->has_missing_marks);
    }

    // ── Test: only published exams count ─────────────────────────────────────────

    public function test_only_published_exams_included(): void
    {
        $year        = $this->makeYear();
        [, $section] = $this->makeSection($year);
        $student     = $this->makeStudent($section, $year);
        $subject     = $this->makeSubject(1.0);

        $published   = $this->makeExam($year, 1, weight: 1.0, published: true);
        $draft       = $this->makeExam($year, 1, weight: 1.0, published: false);

        $this->mark($published, $student, $subject, 80);
        $this->mark($draft,     $student, $subject, 20); // should be ignored

        $this->service()->compute($year, 1);

        $tr = TermResult::where(['semester' => 1, 'student_id' => $student->id])->firstOrFail();
        $this->assertEquals(80.00, $tr->average); // draft exam excluded → average = 80
    }

    // ── Test: finalized blocks recompute ─────────────────────────────────────────

    public function test_finalized_blocks_recompute(): void
    {
        $year        = $this->makeYear();
        [, $section] = $this->makeSection($year);
        $student     = $this->makeStudent($section, $year);
        $subject     = $this->makeSubject(1.0);
        $exam        = $this->makeExam($year, 1);
        $this->mark($exam, $student, $subject, 80);

        $this->service()->compute($year, 1);

        // Finalize
        TermResult::where(['academic_year_id' => $year->id, 'semester' => 1])
            ->update(['is_finalized' => true]);

        // Change the mark (simulating a correction attempt)
        ExamMark::where(['exam_id' => $exam->id, 'student_id' => $student->id])
            ->update(['score' => 30]);

        $result = $this->service()->compute($year, 1);

        $this->assertFalse($result); // returns false when locked
        $tr = TermResult::where(['semester' => 1, 'student_id' => $student->id])->first();
        $this->assertEquals(80.00, $tr->average); // unchanged
    }

    // ── Test: authorization — only admin/principal can compute ───────────────────

    public function test_non_admin_cannot_compute(): void
    {
        $year   = $this->makeYear();
        $teacher = User::factory()->create(['status' => 'active']);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)
            ->post(route('term-results.compute'), [
                'academic_year_id' => $year->id,
                'semester'         => 1,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_compute(): void
    {
        $year  = $this->makeYear();
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('term-results.compute'), [
                'academic_year_id' => $year->id,
                'semester'         => 1,
            ])
            ->assertRedirect();
    }

    // ── Test: student sees only own published term result ────────────────────────

    public function test_student_sees_only_own_published_term_result(): void
    {
        $year        = $this->makeYear();
        [, $section] = $this->makeSection($year);

        $studentUser = User::factory()->create(['status' => 'active']);
        $studentUser->assignRole('student');
        $student = Student::create([
            'user_id' => $studentUser->id, 'name_en' => 'Alice', 'name_km' => null,
            'student_code' => 'S-ALICE', 'gender' => 'female', 'status' => 'enrolled',
        ]);
        $section->students()->attach($student->id, ['academic_year_id' => $year->id]);

        $subject = $this->makeSubject();
        $exam    = $this->makeExam($year, 1);
        $this->mark($exam, $student, $subject, 75);
        $this->service()->compute($year, 1);

        // Not published yet → 403
        $this->actingAs($studentUser)
            ->get(route('term-results.show', [$year, '1', $student]))
            ->assertForbidden();

        // Publish → 200
        TermResult::where(['student_id' => $student->id, 'semester' => 1])
            ->update(['is_published' => true]);

        $this->actingAs($studentUser)
            ->get(route('term-results.show', [$year, '1', $student]))
            ->assertOk()
            ->assertSee('Alice');
    }

    // ── Test: parent IDOR — sees only own child ──────────────────────────────────

    public function test_parent_cannot_view_another_childs_term_result(): void
    {
        $year        = $this->makeYear();
        [, $section] = $this->makeSection($year);

        $parentUser = User::factory()->create(['status' => 'active']);
        $parentUser->assignRole('parent');

        // Another student not linked to this parent
        $otherStudentUser = User::factory()->create(['status' => 'active']);
        $otherStudent = Student::create([
            'user_id' => $otherStudentUser->id, 'name_en' => 'Bob', 'name_km' => null,
            'student_code' => 'S-BOB', 'gender' => 'male', 'status' => 'enrolled',
        ]);
        $section->students()->attach($otherStudent->id, ['academic_year_id' => $year->id]);

        $subject = $this->makeSubject();
        $exam    = $this->makeExam($year, 1);
        $this->mark($exam, $otherStudent, $subject, 75);
        $this->service()->compute($year, 1);
        TermResult::where(['student_id' => $otherStudent->id, 'semester' => 1])
            ->update(['is_published' => true]);

        // Parent NOT linked → 403
        $this->actingAs($parentUser)
            ->get(route('term-results.show', [$year, '1', $otherStudent]))
            ->assertForbidden();
    }

    // ── Test: annual consolidation ───────────────────────────────────────────────

    public function test_annual_combines_semester_averages(): void
    {
        $year        = $this->makeYear();
        [, $section] = $this->makeSection($year);
        $student     = $this->makeStudent($section, $year);
        $subject     = $this->makeSubject(1.0);

        $examS1 = $this->makeExam($year, 1);
        $examS2 = $this->makeExam($year, 2);
        $this->mark($examS1, $student, $subject, 80);
        $this->mark($examS2, $student, $subject, 60);

        $this->service()->compute($year, 1);
        $this->service()->compute($year, 2);
        $this->service()->compute($year, null); // annual

        $annual = TermResult::where(['academic_year_id' => $year->id, 'semester' => null, 'student_id' => $student->id])->firstOrFail();
        // S1 avg=80, S2 avg=60 → annual avg = (80+60)/2 = 70
        $this->assertEquals(70.00, $annual->average);
    }

    // ── Test: exam created through the real HTTP form feeds term computation ─────
    // Regression test — every other exam in this file is built with makeExam(),
    // which sets `semester` directly on the model and never touches the controller
    // or its validation. That let the create/edit forms ship without a `semester`
    // field: exams made through the UI got semester=null and were silently
    // excluded here. This test goes through the actual store route instead.

    public function test_exam_created_via_http_form_is_included_in_term_computation(): void
    {
        $year        = $this->makeYear();
        [, $section] = $this->makeSection($year);
        $student     = $this->makeStudent($section, $year);
        $subject     = $this->makeSubject(1.0);

        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('exams.store'), [
                'academic_year_id' => $year->id,
                'name'             => 'HTTP Midterm',
                'type'             => 'midterm',
                'semester'         => 1,
                'weight'           => 1,
            ])
            ->assertRedirect(route('exams.index'));

        $exam = Exam::where('name', 'HTTP Midterm')->firstOrFail();
        $this->assertEquals(1, $exam->semester);
        $this->assertEquals(1.00, $exam->weight);

        $this->mark($exam, $student, $subject, 80);

        $this->actingAs($admin)->post(route('exams.publish', $exam));

        $this->service()->compute($year, 1);

        $tr = TermResult::where(['academic_year_id' => $year->id, 'semester' => 1, 'student_id' => $student->id])->first();
        $this->assertNotNull($tr, 'Exam created via the HTTP form was not picked up by term computation.');
        $this->assertEquals(80.00, $tr->average);
    }

    // ── Test: exam store rejects a missing semester ──────────────────────────────

    public function test_exam_store_requires_semester(): void
    {
        $year  = $this->makeYear();
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('exams.store'), [
                'academic_year_id' => $year->id,
                'name'             => 'No Semester Exam',
                'type'             => 'midterm',
                'weight'           => 1,
            ])
            ->assertSessionHasErrors('semester');

        $this->assertDatabaseMissing('exams', ['name' => 'No Semester Exam']);
    }
}
