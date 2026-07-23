<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\TermResult;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TermRankingTest extends TestCase
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

    private function makeStudent(string $name): Student
    {
        return Student::create([
            'student_code' => 'S-'.uniqid(), 'name_en' => $name,
            'gender' => 'male', 'status' => 'enrolled',
        ]);
    }

    private function makeSection(string $className): Section
    {
        $class = SchoolClass::create(['name' => $className, 'level' => $className, 'capacity' => 30]);

        return Section::create(['school_class_id' => $class->id, 'name' => 'A']);
    }

    /**
     * Two grades: Grade 7 (Alice=90, Bob=90 tied, Carol=70) and Grade 8
     * (Dave=85). School-wide sorted desc: Alice/Bob tied #1, Dave #3 (a
     * standard-competition tie skips #2), Carol #4. Grade-level: Grade 7
     * has its own Alice/Bob tied #1, Carol #3 (same skip, within just that
     * grade); Grade 8's Dave is #1 alone in his grade despite being #3
     * school-wide — this is exactly the case term_results.rank cannot
     * already answer, since it's computed per-section only.
     */
    private function seedRankingFixture(?int $semester = 1): AcademicYear
    {
        $year = AcademicYear::create([
            'name' => '2026-2027', 'start_date' => '2026-08-01', 'end_date' => '2027-05-31', 'is_active' => true,
        ]);

        $grade7 = $this->makeSection('Grade 7');
        $grade8 = $this->makeSection('Grade 8');

        $students = [
            'Alice' => ['section' => $grade7, 'average' => 90],
            'Bob'   => ['section' => $grade7, 'average' => 90],
            'Carol' => ['section' => $grade7, 'average' => 70],
            'Dave'  => ['section' => $grade8, 'average' => 85],
        ];

        foreach ($students as $name => $data) {
            $student = $this->makeStudent($name);
            $student->sections()->attach($data['section']->id, ['academic_year_id' => $year->id]);

            TermResult::create([
                'academic_year_id' => $year->id,
                'semester' => $semester,
                'student_id' => $student->id,
                'section_id' => $data['section']->id,
                'total' => $data['average'] * 5,
                'average' => $data['average'],
                'gpa' => round($data['average'] / 25, 2),
                'result' => $data['average'] >= 50 ? 'pass' : 'fail',
                'is_published' => true,
            ]);
        }

        return $year;
    }

    private function rankFor(\Illuminate\Support\Collection $ranking, string $name): ?object
    {
        return $ranking->firstWhere('name_en', $name);
    }

    // ── Ranking correctness ──────────────────────────────────────────────────

    public function test_school_wide_rank_spans_every_grade_with_standard_competition_ties(): void
    {
        $year = $this->seedRankingFixture();

        $response = $this->actingAs($this->makeUser('admin'))
            ->get(route('term-ranking.show', [$year, '1']));

        $response->assertOk();
        $ranking = $response->viewData('ranking');

        $this->assertEquals(1, $this->rankFor($ranking, 'Alice')->school_rank);
        $this->assertEquals(1, $this->rankFor($ranking, 'Bob')->school_rank);
        $this->assertEquals(3, $this->rankFor($ranking, 'Dave')->school_rank); // tie above skips #2
        $this->assertEquals(4, $this->rankFor($ranking, 'Carol')->school_rank);
    }

    public function test_grade_level_rank_is_independent_per_grade_not_derived_from_school_rank(): void
    {
        $year = $this->seedRankingFixture();

        $response = $this->actingAs($this->makeUser('admin'))
            ->get(route('term-ranking.show', [$year, '1']));

        $ranking = $response->viewData('ranking');

        // Dave is school_rank 3 but the only student in Grade 8 — must be
        // class_rank 1 there, not 3.
        $this->assertEquals(1, $this->rankFor($ranking, 'Dave')->class_rank);

        // Grade 7's own tie-then-skip, independent of Grade 8 entirely.
        $this->assertEquals(1, $this->rankFor($ranking, 'Alice')->class_rank);
        $this->assertEquals(1, $this->rankFor($ranking, 'Bob')->class_rank);
        $this->assertEquals(3, $this->rankFor($ranking, 'Carol')->class_rank);
    }

    public function test_unpublished_term_results_are_excluded(): void
    {
        $year = $this->seedRankingFixture();
        $section = $this->makeSection('Grade 9');
        $hidden = $this->makeStudent('Hidden');
        $hidden->sections()->attach($section->id, ['academic_year_id' => $year->id]);
        TermResult::create([
            'academic_year_id' => $year->id, 'semester' => 1, 'student_id' => $hidden->id,
            'section_id' => $section->id, 'total' => 500, 'average' => 100, 'gpa' => 4,
            'result' => 'pass', 'is_published' => false,
        ]);

        $response = $this->actingAs($this->makeUser('admin'))
            ->get(route('term-ranking.show', [$year, '1']));

        $ranking = $response->viewData('ranking');
        $this->assertNull($this->rankFor($ranking, 'Hidden'));
        $this->assertCount(4, $ranking);
    }

    public function test_annual_slug_resolves_to_null_semester(): void
    {
        $year = $this->seedRankingFixture(semester: null);

        $response = $this->actingAs($this->makeUser('admin'))
            ->get(route('term-ranking.show', [$year, 'annual']));

        $response->assertOk();
        $this->assertCount(4, $response->viewData('ranking'));
    }

    public function test_semester_1_and_semester_2_do_not_mix(): void
    {
        $year = $this->seedRankingFixture(semester: 1);
        $section = $this->makeSection('Grade 10');
        $sem2Student = $this->makeStudent('SemTwo');
        $sem2Student->sections()->attach($section->id, ['academic_year_id' => $year->id]);
        TermResult::create([
            'academic_year_id' => $year->id, 'semester' => 2, 'student_id' => $sem2Student->id,
            'section_id' => $section->id, 'total' => 400, 'average' => 80, 'gpa' => 3.2,
            'result' => 'pass', 'is_published' => true,
        ]);

        $response = $this->actingAs($this->makeUser('admin'))
            ->get(route('term-ranking.show', [$year, '1']));

        $ranking = $response->viewData('ranking');
        $this->assertCount(4, $ranking);
        $this->assertNull($this->rankFor($ranking, 'SemTwo'));
    }

    // ── Permissions ──────────────────────────────────────────────────────────

    public function test_principal_can_view_term_ranking(): void
    {
        $year = $this->seedRankingFixture();

        $this->actingAs($this->makeUser('principal'))
            ->get(route('term-ranking.show', [$year, '1']))
            ->assertOk();
    }

    public function test_teacher_cannot_view_term_ranking(): void
    {
        $year = $this->seedRankingFixture();

        $this->actingAs($this->makeUser('teacher'))
            ->get(route('term-ranking.show', [$year, '1']))
            ->assertForbidden();
    }

    public function test_index_requires_term_results_manage(): void
    {
        $this->actingAs($this->makeUser('accountant'))
            ->get(route('term-ranking.index'))
            ->assertForbidden();
    }

    // ── Downloads ────────────────────────────────────────────────────────────

    public function test_excel_export_downloads_successfully(): void
    {
        $year = $this->seedRankingFixture();

        $this->actingAs($this->makeUser('admin'))
            ->get(route('term-ranking.excel', [$year, '1']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_pdf_export_downloads_successfully(): void
    {
        $year = $this->seedRankingFixture();

        $this->actingAs($this->makeUser('admin'))
            ->get(route('term-ranking.pdf', [$year, '1']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    // ── Index picker ─────────────────────────────────────────────────────────

    public function test_index_lists_only_published_periods(): void
    {
        $year = $this->seedRankingFixture();

        $response = $this->actingAs($this->makeUser('admin'))->get(route('term-ranking.index'));

        $response->assertOk();
        $response->assertSee($year->name);
    }
}
