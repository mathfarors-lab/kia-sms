<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeScale;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\TermResult;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranscriptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        GradeScale::insert([
            ['grade' => 'F', 'min_score' =>  0, 'max_score' => 49, 'gpa' => 0.00],
            ['grade' => 'C', 'min_score' => 50, 'max_score' => 69, 'gpa' => 2.00],
            ['grade' => 'B', 'min_score' => 70, 'max_score' => 89, 'gpa' => 3.00],
            ['grade' => 'A', 'min_score' => 90, 'max_score' => 100, 'gpa' => 4.00],
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    private function makeYear(string $name = 'Year 2026'): AcademicYear
    {
        return AcademicYear::create([
            'name' => $name, 'start_date' => '2026-01-01',
            'end_date' => '2026-12-31', 'is_active' => true,
        ]);
    }

    private function makeStudentUser(): array
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('student');
        $student = Student::create([
            'user_id'      => $user->id,
            'name_en'      => 'Student-' . uniqid(),
            'name_km'      => null,
            'student_code' => 'S-' . uniqid(),
            'gender'       => 'female',
            'status'       => 'enrolled',
        ]);
        return [$user, $student];
    }

    private function makeSection(AcademicYear $year, Student $student): Section
    {
        static $i = 0;
        $i++;
        $class   = SchoolClass::create(['name' => "Grade T$i", 'level' => 'High', 'capacity' => 30]);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);
        $section->students()->attach($student->id, ['academic_year_id' => $year->id]);
        return $section;
    }

    private function publishedResult(Student $student, AcademicYear $year, Section $section, int $semester): TermResult
    {
        return TermResult::create([
            'student_id'       => $student->id,
            'academic_year_id' => $year->id,
            'section_id'       => $section->id,
            'semester'         => $semester,
            'total'            => 80,
            'average'          => 80.00,
            'gpa'              => 3.00,
            'rank'             => 1,
            'result'           => 'pass',
            'is_published'     => true,
            'is_finalized'     => true,
            'has_missing_marks'=> false,
        ]);
    }

    private function draftResult(Student $student, AcademicYear $year, Section $section, int $semester): TermResult
    {
        return TermResult::create([
            'student_id'       => $student->id,
            'academic_year_id' => $year->id,
            'section_id'       => $section->id,
            'semester'         => $semester,
            'total'            => 60,
            'average'          => 60.00,
            'gpa'              => 2.00,
            'rank'             => 2,
            'result'           => 'pass',
            'is_published'     => false,
            'is_finalized'     => false,
            'has_missing_marks'=> false,
        ]);
    }

    // ── Tests ─────────────────────────────────────────────────────────────────────

    /** Admin can view a student transcript. */
    public function test_admin_can_view_transcript(): void
    {
        $year    = $this->makeYear();
        [$user, $student] = $this->makeStudentUser();
        $section = $this->makeSection($year, $student);
        $this->publishedResult($student, $year, $section, 1);

        $this->actingAs($this->makeAdmin())
            ->get(route('transcripts.show', $student))
            ->assertOk()
            ->assertSee($student->name_en);
    }

    /** Transcript shows year block when there are published results. */
    public function test_transcript_shows_published_results(): void
    {
        $year    = $this->makeYear('Year 2026');
        [$user, $student] = $this->makeStudentUser();
        $section = $this->makeSection($year, $student);
        $this->publishedResult($student, $year, $section, 1);

        $this->actingAs($this->makeAdmin())
            ->get(route('transcripts.show', $student))
            ->assertOk()
            ->assertSee('Year 2026');
    }

    /** Draft results are not shown — no-published-results placeholder appears. */
    public function test_transcript_hides_draft_results(): void
    {
        $year    = $this->makeYear();
        [$user, $student] = $this->makeStudentUser();
        $section = $this->makeSection($year, $student);
        $this->draftResult($student, $year, $section, 1);

        $this->actingAs($this->makeAdmin())
            ->get(route('transcripts.show', $student))
            ->assertOk()
            ->assertSee('No Published Results', false);
    }

    /** Student can view own transcript. */
    public function test_student_can_view_own_transcript(): void
    {
        $year    = $this->makeYear();
        [$user, $student] = $this->makeStudentUser();
        $section = $this->makeSection($year, $student);
        $this->publishedResult($student, $year, $section, 1);

        $this->actingAs($user)
            ->get(route('transcripts.show', $student))
            ->assertOk();
    }

    /** Student cannot view another student's transcript. */
    public function test_student_cannot_view_another_students_transcript(): void
    {
        $year = $this->makeYear();
        [$userA, $studentA] = $this->makeStudentUser();
        [$userB, $studentB] = $this->makeStudentUser();

        $this->actingAs($userB)
            ->get(route('transcripts.show', $studentA))
            ->assertForbidden();
    }

    /** Parent cannot view an unrelated student's transcript. */
    public function test_parent_cannot_view_unrelated_students_transcript(): void
    {
        $year = $this->makeYear();
        [$userA, $studentA] = $this->makeStudentUser();

        $parentUser = User::factory()->create();
        $parentUser->assignRole('parent');

        $this->actingAs($parentUser)
            ->get(route('transcripts.show', $studentA))
            ->assertForbidden();
    }
}
