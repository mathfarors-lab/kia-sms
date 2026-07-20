<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\GradeScale;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TermResult;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AcademicAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Cache::flush();

        $this->year = AcademicYear::create([
            'name' => 'Y1', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30', 'is_active' => true,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeUser(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);
        return $user;
    }

    private function makeStudent(): Student
    {
        return Student::create([
            'user_id' => User::factory()->create()->id, 'student_code' => 'K-' . uniqid(),
            'name_en' => 'Student-' . uniqid(), 'name_km' => 'សិស្ស', 'gender' => 'female', 'status' => 'enrolled',
        ]);
    }

    private function makeSection(string $className = 'Grade 10'): Section
    {
        static $i = 0;
        $i++;
        $class = SchoolClass::create(['name' => $className . '-' . $i, 'level' => 'High', 'capacity' => 30]);
        return Section::create(['school_class_id' => $class->id, 'name' => 'A']);
    }

    private function makeTermResult(Section $section, array $overrides = []): TermResult
    {
        return TermResult::create(array_merge([
            'student_id'        => $this->makeStudent()->id,
            'academic_year_id'  => $this->year->id,
            'section_id'        => $section->id,
            'semester'          => null,
            'total'             => 80,
            'average'           => 80.00,
            'gpa'               => 3.00,
            'rank'              => 1,
            'result'            => 'pass',
            'is_published'      => true,
            'is_finalized'      => true,
            'has_missing_marks' => false,
        ], $overrides));
    }

    // ── Access control ───────────────────────────────────────────────────────

    public function test_dashboard_requires_analytics_permission(): void
    {
        $this->actingAs($this->makeUser('teacher'))->get(route('academic-analytics.index'))->assertForbidden();
    }

    public function test_admin_can_view_dashboard(): void
    {
        $this->actingAs($this->makeUser('admin'))->get(route('academic-analytics.index'))->assertOk();
    }

    // ── Pass rate ────────────────────────────────────────────────────────────

    public function test_pass_rate_matches_seeded_published_annual_results(): void
    {
        $section = $this->makeSection();
        $this->makeTermResult($section, ['result' => 'pass']);
        $this->makeTermResult($section, ['result' => 'pass']);
        $this->makeTermResult($section, ['result' => 'pass']);
        $this->makeTermResult($section, ['result' => 'fail']);

        $response = $this->actingAs($this->makeUser('admin'))->get(route('academic-analytics.index'));

        $response->assertOk();
        $this->assertEquals(75.0, $response->viewData('passRate'));
    }

    public function test_pass_rate_ignores_unpublished_results(): void
    {
        $section = $this->makeSection();
        $this->makeTermResult($section, ['result' => 'pass', 'is_published' => true]);
        $this->makeTermResult($section, ['result' => 'fail', 'is_published' => false]);

        $response = $this->actingAs($this->makeUser('admin'))->get(route('academic-analytics.index'));

        $this->assertEquals(100.0, $response->viewData('passRate'));
    }

    public function test_pass_rate_counts_only_annual_not_semester_results(): void
    {
        $section = $this->makeSection();
        $this->makeTermResult($section, ['result' => 'pass', 'semester' => null]); // annual — counted
        $this->makeTermResult($section, ['result' => 'fail', 'semester' => 1]);    // semester — excluded

        $response = $this->actingAs($this->makeUser('admin'))->get(route('academic-analytics.index'));

        $this->assertEquals(100.0, $response->viewData('passRate'));
    }

    public function test_pass_rate_is_null_when_nothing_published(): void
    {
        $response = $this->actingAs($this->makeUser('admin'))->get(route('academic-analytics.index'));

        $this->assertNull($response->viewData('passRate'));
    }

    // ── Average by section ──────────────────────────────────────────────────

    public function test_average_by_section_matches_seeded_data(): void
    {
        $sectionA = $this->makeSection('Grade 10');
        $sectionB = $this->makeSection('Grade 11');

        $this->makeTermResult($sectionA, ['average' => 90.00, 'gpa' => 4.00]);
        $this->makeTermResult($sectionA, ['average' => 70.00, 'gpa' => 2.00]);
        $this->makeTermResult($sectionB, ['average' => 60.00, 'gpa' => 1.50]);

        $response = $this->actingAs($this->makeUser('admin'))->get(route('academic-analytics.index'));
        $bySection = collect($response->viewData('bySection'));

        // Both sections are named "A" (only their parent class differs) — this
        // deliberately proves the two rows aren't collapsed into one.
        $this->assertCount(2, $bySection);

        $rowA = $bySection->first(fn ($r) => (float) $r->avg_score === 80.0);
        $this->assertNotNull($rowA, 'Section A (avg 90+70)/2=80 not found');
        $this->assertEquals(2, $rowA->student_count);
        $this->assertEquals(3.00, round((float) $rowA->avg_gpa, 2));

        $rowB = $bySection->first(fn ($r) => (float) $r->avg_score === 60.0);
        $this->assertNotNull($rowB);
        $this->assertEquals(1, $rowB->student_count);
    }

    /** Both lists sort highest-first and flag the top/bottom row — proven against known, distinctly-scored data. */
    public function test_section_and_subject_lists_are_sorted_with_highest_and_lowest_flagged(): void
    {
        $top    = $this->makeSection('TopClass');
        $middle = $this->makeSection('MiddleClass');
        $bottom = $this->makeSection('BottomClass');
        $this->makeTermResult($top, ['average' => 95.00]);
        $this->makeTermResult($middle, ['average' => 75.00]);
        $this->makeTermResult($bottom, ['average' => 40.00]);

        $subject1 = $this->makeSubject('History');
        $subject2 = $this->makeSubject('Physics');
        $exam = Exam::create(['academic_year_id' => $this->year->id, 'name' => 'Final', 'type' => 'final', 'is_published' => true]);
        ExamMark::create(['exam_id' => $exam->id, 'student_id' => $this->makeStudent()->id, 'subject_id' => $subject1->id, 'score' => 88]);
        ExamMark::create(['exam_id' => $exam->id, 'student_id' => $this->makeStudent()->id, 'subject_id' => $subject2->id, 'score' => 42]);

        // makeSection() suffixes a cross-test static counter onto the class
        // name, so capture the REAL generated names rather than guessing them.
        $topClassName    = $top->schoolClass->name;
        $middleClassName = $middle->schoolClass->name;
        $bottomClassName = $bottom->schoolClass->name;

        $response = $this->actingAs($this->makeUser('admin'))->get(route('academic-analytics.index'));
        $response->assertOk();

        // Data is sorted highest-average-first.
        $bySection = $response->viewData('bySection');
        $this->assertEquals(
            [$topClassName, $middleClassName, $bottomClassName],
            array_map(fn ($r) => $r->class_name, $bySection)
        );

        $subjectAverages = $response->viewData('subjectAverages');
        $this->assertEquals(['History', 'Physics'], array_map(fn ($r) => $r->name_en, $subjectAverages));

        // The correct row is visually flagged — highest label attached to the
        // top row, lowest to the bottom row, in that order in the markup.
        $response->assertSeeInOrder([
            $topClassName, __('academic_analytics.highest'),
            $bottomClassName, __('academic_analytics.lowest'),
        ]);
        $response->assertSeeInOrder([
            'History', __('academic_analytics.highest'),
            'Physics', __('academic_analytics.lowest'),
        ]);

        // Exactly one "highest" and one "lowest" label per table (2 tables =
        // 2 each) — the middle section/subject must never be flagged.
        $content = $response->getContent();
        $this->assertEquals(2, substr_count($content, __('academic_analytics.highest')));
        $this->assertEquals(2, substr_count($content, __('academic_analytics.lowest')));
    }

    // ── Subject averages ─────────────────────────────────────────────────────

    private function makeSubject(string $name = 'Math'): Subject
    {
        return Subject::create(['name_en' => $name, 'name_km' => $name, 'code' => strtoupper($name) . '-' . uniqid(), 'full_mark' => 100, 'coefficient' => 1]);
    }

    public function test_subject_averages_counts_only_published_exam_marks(): void
    {
        $subject = $this->makeSubject('Math');

        $publishedExam = Exam::create(['academic_year_id' => $this->year->id, 'name' => 'Midterm', 'type' => 'midterm', 'is_published' => true]);
        $draftExam     = Exam::create(['academic_year_id' => $this->year->id, 'name' => 'Draft Monthly', 'type' => 'monthly', 'is_published' => false]);

        ExamMark::create(['exam_id' => $publishedExam->id, 'student_id' => $this->makeStudent()->id, 'subject_id' => $subject->id, 'score' => 80]);
        ExamMark::create(['exam_id' => $publishedExam->id, 'student_id' => $this->makeStudent()->id, 'subject_id' => $subject->id, 'score' => 60]);
        ExamMark::create(['exam_id' => $draftExam->id, 'student_id' => $this->makeStudent()->id, 'subject_id' => $subject->id, 'score' => 0]);

        $response = $this->actingAs($this->makeUser('admin'))->get(route('academic-analytics.index'));
        $row = collect($response->viewData('subjectAverages'))->firstWhere('name_en', 'Math');

        $this->assertNotNull($row);
        $this->assertEquals(70.0, (float) $row->avg_score); // (80+60)/2, draft exam excluded
        $this->assertEquals(2, $row->mark_count);
    }

    // ── Grade distribution ───────────────────────────────────────────────────

    public function test_grade_distribution_matches_seeded_grade_scale_ranges(): void
    {
        GradeScale::insert([
            ['grade' => 'F', 'min_score' => 0,  'max_score' => 49,  'gpa' => 0.00, 'created_at' => now(), 'updated_at' => now()],
            ['grade' => 'C', 'min_score' => 50, 'max_score' => 69,  'gpa' => 2.00, 'created_at' => now(), 'updated_at' => now()],
            ['grade' => 'B', 'min_score' => 70, 'max_score' => 89,  'gpa' => 3.00, 'created_at' => now(), 'updated_at' => now()],
            ['grade' => 'A', 'min_score' => 90, 'max_score' => 100, 'gpa' => 4.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $subject = $this->makeSubject();
        $exam = Exam::create(['academic_year_id' => $this->year->id, 'name' => 'Final', 'type' => 'final', 'is_published' => true]);

        foreach ([95, 92, 75, 55, 20] as $score) {
            ExamMark::create(['exam_id' => $exam->id, 'student_id' => $this->makeStudent()->id, 'subject_id' => $subject->id, 'score' => $score]);
        }

        $response = $this->actingAs($this->makeUser('admin'))->get(route('academic-analytics.index'));
        $distribution = collect($response->viewData('gradeDistribution'))->keyBy('grade');

        $this->assertEquals(2, (int) $distribution['A']->total); // 95, 92
        $this->assertEquals(1, (int) $distribution['B']->total); // 75
        $this->assertEquals(1, (int) $distribution['C']->total); // 55
        $this->assertEquals(1, (int) $distribution['F']->total); // 20
    }
}
