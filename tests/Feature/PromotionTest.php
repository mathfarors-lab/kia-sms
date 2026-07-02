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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────────

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    private function makePrincipal(): User
    {
        $user = User::factory()->create();
        $user->assignRole('principal');
        return $user;
    }

    private function makeTeacher(): User
    {
        $user = User::factory()->create();
        $user->assignRole('teacher');
        return $user;
    }

    private function makeYear(string $name = 'Year A', bool $active = false): AcademicYear
    {
        return AcademicYear::create([
            'name'       => $name,
            'start_date' => '2026-01-01',
            'end_date'   => '2026-12-31',
            'is_active'  => $active,
        ]);
    }

    private function makeClass(string $name, ?SchoolClass $nextClass = null): SchoolClass
    {
        return SchoolClass::create([
            'name'          => $name,
            'level'         => 'Primary',
            'capacity'      => 30,
            'next_class_id' => $nextClass?->id,
        ]);
    }

    private function makeSection(SchoolClass $class, string $name = 'A'): Section
    {
        return Section::create(['school_class_id' => $class->id, 'name' => $name]);
    }

    private function makeStudentUser(string $status = 'enrolled'): array
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('student');
        $student = Student::create([
            'user_id'      => $user->id,
            'name_en'      => 'Student-' . uniqid(),
            'name_km'      => null,
            'student_code' => 'S-' . uniqid(),
            'gender'       => 'female',
            'status'       => $status,
        ]);
        return [$user, $student];
    }

    private function enroll(Student $student, Section $section, AcademicYear $year): void
    {
        DB::table('student_section')->insertOrIgnore([
            'student_id'       => $student->id,
            'section_id'       => $section->id,
            'academic_year_id' => $year->id,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    private function makeAnnualResult(Student $student, Section $section, AcademicYear $year, string $result): TermResult
    {
        return TermResult::create([
            'student_id'        => $student->id,
            'academic_year_id'  => $year->id,
            'section_id'        => $section->id,
            'semester'          => null,  // null = annual
            'total'             => $result === 'pass' ? 200 : 80,
            'average'           => $result === 'pass' ? 80.00 : 40.00,
            'gpa'               => $result === 'pass' ? 3.00 : 1.00,
            'rank'              => 1,
            'result'            => $result,
            'is_published'      => true,
            'is_finalized'      => true,
            'has_missing_marks' => false,
        ]);
    }

    // ── Step 0: Year-scoped pivot filter ─────────────────────────────────────────

    /**
     * A student enrolled only in yearA MUST NOT appear in yearB's pivot query.
     * This validates the wherePivot('academic_year_id', ...) pattern used throughout
     * TermGradingService, IdCardController, and PromotionService.
     */
    public function test_pivot_filter_excludes_students_from_other_year(): void
    {
        $yearA = $this->makeYear('Year A');
        $yearB = $this->makeYear('Year B');
        $class   = $this->makeClass('Grade 1');
        $section = $this->makeSection($class);

        [, $studentA] = $this->makeStudentUser();
        [, $studentB] = $this->makeStudentUser();

        $this->enroll($studentA, $section, $yearA);
        $this->enroll($studentB, $section, $yearB);

        $yearAStudentIds = $section->students()
            ->wherePivot('academic_year_id', $yearA->id)
            ->pluck('students.id');

        $yearBStudentIds = $section->students()
            ->wherePivot('academic_year_id', $yearB->id)
            ->pluck('students.id');

        $this->assertContains($studentA->id, $yearAStudentIds->all());
        $this->assertNotContains($studentB->id, $yearAStudentIds->all());

        $this->assertContains($studentB->id, $yearBStudentIds->all());
        $this->assertNotContains($studentA->id, $yearBStudentIds->all());
    }

    // ── Authorization ──────────────────────────────────────────────────────────────

    public function test_teacher_cannot_access_promotion_preview(): void
    {
        $fromYear = $this->makeYear('From');
        $toYear   = $this->makeYear('To');

        $this->actingAs($this->makeTeacher())
            ->post(route('promotion.preview'), [
                'from_year_id' => $fromYear->id,
                'to_year_id'   => $toYear->id,
            ])
            ->assertForbidden();
    }

    public function test_teacher_cannot_execute_promotion(): void
    {
        $fromYear = $this->makeYear('From');
        $toYear   = $this->makeYear('To');

        $this->actingAs($this->makeTeacher())
            ->post(route('promotion.execute'), [
                'from_year_id' => $fromYear->id,
                'to_year_id'   => $toYear->id,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_access_promotion_index(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('promotion.index'))
            ->assertOk();
    }

    public function test_principal_can_access_promotion_index(): void
    {
        $this->actingAs($this->makePrincipal())
            ->get(route('promotion.index'))
            ->assertOk();
    }

    // ── Dry-run (preview) ─────────────────────────────────────────────────────────

    public function test_dry_run_preview_changes_nothing_in_db(): void
    {
        $fromYear = $this->makeYear('Year 2026', true);
        $toYear   = $this->makeYear('Year 2027');
        $classA   = $this->makeClass('Grade 1');
        $classB   = $this->makeClass('Grade 2');
        $classA->update(['next_class_id' => $classB->id]);
        $sectionA = $this->makeSection($classA);
        $this->makeSection($classB, 'A');

        [, $student] = $this->makeStudentUser();
        $this->enroll($student, $sectionA, $fromYear);
        $this->makeAnnualResult($student, $sectionA, $fromYear, 'pass');

        $countBefore = DB::table('student_section')->count();

        $this->actingAs($this->makeAdmin())
            ->post(route('promotion.preview'), [
                'from_year_id' => $fromYear->id,
                'to_year_id'   => $toYear->id,
            ])
            ->assertOk();

        $this->assertEquals($countBefore, DB::table('student_section')->count());
        $this->assertDatabaseMissing('student_section', [
            'student_id'       => $student->id,
            'academic_year_id' => $toYear->id,
        ]);
    }

    // ── Core promotion logic ──────────────────────────────────────────────────────

    public function test_promote_passing_student_to_next_class(): void
    {
        $fromYear = $this->makeYear('Year 2026', true);
        $toYear   = $this->makeYear('Year 2027');
        $classA   = $this->makeClass('Grade 1');
        $classB   = $this->makeClass('Grade 2');
        $classA->update(['next_class_id' => $classB->id]);
        $sectionA = $this->makeSection($classA);
        $sectionB = $this->makeSection($classB);

        [, $student] = $this->makeStudentUser('enrolled');
        $this->enroll($student, $sectionA, $fromYear);
        $this->makeAnnualResult($student, $sectionA, $fromYear, 'pass');

        $this->actingAs($this->makeAdmin())
            ->post(route('promotion.execute'), [
                'from_year_id' => $fromYear->id,
                'to_year_id'   => $toYear->id,
            ])
            ->assertOk();

        // New enrollment in sectionB (Grade 2) for toYear
        $this->assertDatabaseHas('student_section', [
            'student_id'       => $student->id,
            'section_id'       => $sectionB->id,
            'academic_year_id' => $toYear->id,
        ]);

        // Old enrollment in sectionA is untouched
        $this->assertDatabaseHas('student_section', [
            'student_id'       => $student->id,
            'section_id'       => $sectionA->id,
            'academic_year_id' => $fromYear->id,
        ]);
    }

    public function test_graduate_passing_student_in_final_class(): void
    {
        $fromYear = $this->makeYear('Year 2026', true);
        $toYear   = $this->makeYear('Year 2027');

        // Grade 12 — no next_class_id (final)
        $grade12  = $this->makeClass('Grade 12');
        $section  = $this->makeSection($grade12);

        [, $student] = $this->makeStudentUser('enrolled');
        $this->enroll($student, $section, $fromYear);
        $this->makeAnnualResult($student, $section, $fromYear, 'pass');

        $this->actingAs($this->makeAdmin())
            ->post(route('promotion.execute'), [
                'from_year_id' => $fromYear->id,
                'to_year_id'   => $toYear->id,
            ])
            ->assertOk();

        // Student status is now graduated
        $this->assertDatabaseHas('students', [
            'id'     => $student->id,
            'status' => 'graduated',
        ]);

        // No new enrollment in toYear
        $this->assertDatabaseMissing('student_section', [
            'student_id'       => $student->id,
            'academic_year_id' => $toYear->id,
        ]);
    }

    public function test_retain_failing_student_in_same_section(): void
    {
        $fromYear = $this->makeYear('Year 2026', true);
        $toYear   = $this->makeYear('Year 2027');
        $classA   = $this->makeClass('Grade 1');
        $classB   = $this->makeClass('Grade 2');
        $classA->update(['next_class_id' => $classB->id]);
        $sectionA = $this->makeSection($classA);
        $this->makeSection($classB);

        [, $student] = $this->makeStudentUser('enrolled');
        $this->enroll($student, $sectionA, $fromYear);
        $this->makeAnnualResult($student, $sectionA, $fromYear, 'fail');

        $this->actingAs($this->makeAdmin())
            ->post(route('promotion.execute'), [
                'from_year_id' => $fromYear->id,
                'to_year_id'   => $toYear->id,
            ])
            ->assertOk();

        // Re-enrolled in same sectionA for new year
        $this->assertDatabaseHas('student_section', [
            'student_id'       => $student->id,
            'section_id'       => $sectionA->id,
            'academic_year_id' => $toYear->id,
        ]);

        // NOT promoted to Grade 2
        $this->assertDatabaseMissing('student_section', [
            'student_id'       => $student->id,
            'section_id'       => $this->makeSection($classB, 'Z')->id,
            'academic_year_id' => $toYear->id,
        ]);
    }

    public function test_retain_student_with_no_annual_result(): void
    {
        $fromYear = $this->makeYear('Year 2026', true);
        $toYear   = $this->makeYear('Year 2027');
        $classA   = $this->makeClass('Grade 1');
        $classB   = $this->makeClass('Grade 2');
        $classA->update(['next_class_id' => $classB->id]);
        $sectionA = $this->makeSection($classA);
        $this->makeSection($classB);

        [, $student] = $this->makeStudentUser('enrolled');
        $this->enroll($student, $sectionA, $fromYear);
        // No annual result — default is retain

        $this->actingAs($this->makeAdmin())
            ->post(route('promotion.execute'), [
                'from_year_id' => $fromYear->id,
                'to_year_id'   => $toYear->id,
            ])
            ->assertOk();

        // Retained in same section (conservative default)
        $this->assertDatabaseHas('student_section', [
            'student_id'       => $student->id,
            'section_id'       => $sectionA->id,
            'academic_year_id' => $toYear->id,
        ]);
    }

    // ── Per-student overrides ─────────────────────────────────────────────────────

    public function test_override_force_retain_passing_student(): void
    {
        $fromYear = $this->makeYear('Year 2026', true);
        $toYear   = $this->makeYear('Year 2027');
        $classA   = $this->makeClass('Grade 1');
        $classB   = $this->makeClass('Grade 2');
        $classA->update(['next_class_id' => $classB->id]);
        $sectionA = $this->makeSection($classA);
        $sectionB = $this->makeSection($classB);

        [, $student] = $this->makeStudentUser('enrolled');
        $this->enroll($student, $sectionA, $fromYear);
        $this->makeAnnualResult($student, $sectionA, $fromYear, 'pass');

        $this->actingAs($this->makeAdmin())
            ->post(route('promotion.execute'), [
                'from_year_id' => $fromYear->id,
                'to_year_id'   => $toYear->id,
                'overrides'    => [$student->id => 'retain'],
            ])
            ->assertOk();

        // Stayed in sectionA (retained), not promoted to sectionB
        $this->assertDatabaseHas('student_section', [
            'student_id'       => $student->id,
            'section_id'       => $sectionA->id,
            'academic_year_id' => $toYear->id,
        ]);
        $this->assertDatabaseMissing('student_section', [
            'student_id'       => $student->id,
            'section_id'       => $sectionB->id,
            'academic_year_id' => $toYear->id,
        ]);
    }

    public function test_override_force_withdraw_student(): void
    {
        $fromYear = $this->makeYear('Year 2026', true);
        $toYear   = $this->makeYear('Year 2027');
        $class    = $this->makeClass('Grade 1');
        $section  = $this->makeSection($class);

        [, $student] = $this->makeStudentUser('enrolled');
        $this->enroll($student, $section, $fromYear);
        $this->makeAnnualResult($student, $section, $fromYear, 'pass');

        $this->actingAs($this->makeAdmin())
            ->post(route('promotion.execute'), [
                'from_year_id' => $fromYear->id,
                'to_year_id'   => $toYear->id,
                'overrides'    => [$student->id => 'withdraw'],
            ])
            ->assertOk();

        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => 'dropped']);
        $this->assertDatabaseMissing('student_section', [
            'student_id'       => $student->id,
            'academic_year_id' => $toYear->id,
        ]);
    }

    // ── Idempotency ───────────────────────────────────────────────────────────────

    public function test_rerun_is_noop_no_duplicate_enrollments(): void
    {
        $fromYear = $this->makeYear('Year 2026', true);
        $toYear   = $this->makeYear('Year 2027');
        $classA   = $this->makeClass('Grade 1');
        $classB   = $this->makeClass('Grade 2');
        $classA->update(['next_class_id' => $classB->id]);
        $sectionA = $this->makeSection($classA);
        $this->makeSection($classB);

        [, $student] = $this->makeStudentUser('enrolled');
        $this->enroll($student, $sectionA, $fromYear);
        $this->makeAnnualResult($student, $sectionA, $fromYear, 'pass');

        $payload = [
            'from_year_id' => $fromYear->id,
            'to_year_id'   => $toYear->id,
        ];

        // First run
        $this->actingAs($this->makeAdmin())
            ->post(route('promotion.execute'), $payload)
            ->assertOk();

        $countAfterFirst = DB::table('student_section')->count();

        // Second run (should be a no-op — unique constraint skips duplicates)
        $this->actingAs($this->makeAdmin())
            ->post(route('promotion.execute'), $payload)
            ->assertOk();

        $this->assertEquals($countAfterFirst, DB::table('student_section')->count());
    }

    // ── Prior-year data is never modified ────────────────────────────────────────

    public function test_prior_year_enrollments_untouched_after_rollover(): void
    {
        $fromYear = $this->makeYear('Year 2026', true);
        $toYear   = $this->makeYear('Year 2027');
        $class    = $this->makeClass('Grade 1');
        $section  = $this->makeSection($class);

        [, $student] = $this->makeStudentUser('enrolled');
        $this->enroll($student, $section, $fromYear);
        $this->makeAnnualResult($student, $section, $fromYear, 'pass');

        $this->actingAs($this->makeAdmin())
            ->post(route('promotion.execute'), [
                'from_year_id' => $fromYear->id,
                'to_year_id'   => $toYear->id,
            ])
            ->assertOk();

        // Original fromYear enrollment still exists
        $this->assertDatabaseHas('student_section', [
            'student_id'       => $student->id,
            'section_id'       => $section->id,
            'academic_year_id' => $fromYear->id,
        ]);
    }

    public function test_prior_year_results_untouched_after_rollover(): void
    {
        $fromYear = $this->makeYear('Year 2026', true);
        $toYear   = $this->makeYear('Year 2027');
        $class    = $this->makeClass('Grade 1');
        $section  = $this->makeSection($class);

        [, $student] = $this->makeStudentUser('enrolled');
        $this->enroll($student, $section, $fromYear);
        $annualResult = $this->makeAnnualResult($student, $section, $fromYear, 'pass');

        $this->actingAs($this->makeAdmin())
            ->post(route('promotion.execute'), [
                'from_year_id' => $fromYear->id,
                'to_year_id'   => $toYear->id,
            ])
            ->assertOk();

        // Annual result row is unmodified
        $this->assertDatabaseHas('term_results', [
            'id'               => $annualResult->id,
            'student_id'       => $student->id,
            'academic_year_id' => $fromYear->id,
            'result'           => 'pass',
            'is_finalized'     => true,
        ]);
    }

    // ── Active year flag ──────────────────────────────────────────────────────────

    public function test_activate_new_year_flag_switches_active_year(): void
    {
        $fromYear = $this->makeYear('Year 2026', true);
        $toYear   = $this->makeYear('Year 2027', false);
        $class    = $this->makeClass('Grade 1');
        $section  = $this->makeSection($class);

        $this->assertDatabaseHas('academic_years', ['id' => $fromYear->id, 'is_active' => true]);
        $this->assertDatabaseHas('academic_years', ['id' => $toYear->id,   'is_active' => false]);

        $this->actingAs($this->makeAdmin())
            ->post(route('promotion.execute'), [
                'from_year_id'      => $fromYear->id,
                'to_year_id'        => $toYear->id,
                'activate_new_year' => '1',
            ])
            ->assertOk();

        $this->assertDatabaseHas('academic_years', ['id' => $fromYear->id, 'is_active' => false]);
        $this->assertDatabaseHas('academic_years', ['id' => $toYear->id,   'is_active' => true]);
    }
}
