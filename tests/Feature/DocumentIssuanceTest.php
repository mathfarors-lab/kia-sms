<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AdmissionApplication;
use App\Models\IssuedDocument;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\TermResult;
use App\Models\User;
use App\Services\AdmissionService;
use App\Services\PromotionService;
use App\Services\StaffService;
use App\Services\StudentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DocumentIssuanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────

    private function makeAdmin(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('admin');
        return $user;
    }

    private function makeStudent(string $status = 'enrolled'): Student
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('student');
        return app(StudentService::class)->store([
            'name_en' => 'Student-' . uniqid(),
            'name_km' => null,
            'gender'  => 'male',
            'status'  => $status,
            'user_id' => $user->id,
        ]);
    }

    private function makeYear(string $name, bool $active = false): AcademicYear
    {
        return AcademicYear::create([
            'name' => $name, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => $active,
        ]);
    }

    private function makeClass(string $name, ?SchoolClass $nextClass = null): SchoolClass
    {
        return SchoolClass::create(['name' => $name, 'level' => 'Primary', 'capacity' => 30, 'next_class_id' => $nextClass?->id]);
    }

    private function makeSection(SchoolClass $class, string $name = 'A'): Section
    {
        return Section::create(['school_class_id' => $class->id, 'name' => $name]);
    }

    private function enroll(Student $student, Section $section, AcademicYear $year): void
    {
        DB::table('student_section')->insertOrIgnore([
            'student_id' => $student->id, 'section_id' => $section->id, 'academic_year_id' => $year->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function makeAnnualResult(Student $student, Section $section, AcademicYear $year, string $result): TermResult
    {
        return TermResult::create([
            'student_id' => $student->id, 'academic_year_id' => $year->id, 'section_id' => $section->id,
            'semester' => null, 'total' => 200, 'average' => 80, 'gpa' => 3, 'rank' => 1, 'result' => $result,
        ]);
    }

    // ── Enrollment (direct creation + admission conversion) ─────────────────────

    public function test_enrolling_a_student_directly_auto_issues_id_card_and_enrollment_certificate(): void
    {
        $student = $this->makeStudent('enrolled');

        $this->assertDatabaseHas('issued_documents', [
            'student_id' => $student->id, 'type' => IssuedDocument::TYPE_ID_CARD, 'number' => null,
        ]);

        $cert = IssuedDocument::where('student_id', $student->id)->where('type', IssuedDocument::TYPE_ENROLLMENT_CERT)->first();
        $this->assertNotNull($cert);
        $this->assertMatchesRegularExpression('/^ENROLL-\d{4}-\d{5}$/', $cert->number);
    }

    public function test_creating_a_student_with_a_non_enrolled_status_does_not_auto_issue_enrollment_documents(): void
    {
        $student = $this->makeStudent('graduated');

        $this->assertDatabaseMissing('issued_documents', ['student_id' => $student->id, 'type' => IssuedDocument::TYPE_ID_CARD]);
        $this->assertDatabaseMissing('issued_documents', ['student_id' => $student->id, 'type' => IssuedDocument::TYPE_ENROLLMENT_CERT]);
    }

    public function test_converting_an_admission_auto_issues_id_card_and_enrollment_certificate(): void
    {
        $application = AdmissionApplication::create([
            'application_no' => 'ADM-TEST-0001',
            'name_en'        => 'Converted Applicant',
            'gender'         => 'female',
            'status'         => 'accepted',
        ]);
        $admin = $this->makeAdmin();

        $student = app(AdmissionService::class)->convertToStudent($application, $admin->id);

        $this->assertDatabaseHas('issued_documents', ['student_id' => $student->id, 'type' => IssuedDocument::TYPE_ID_CARD]);
        $this->assertDatabaseHas('issued_documents', ['student_id' => $student->id, 'type' => IssuedDocument::TYPE_ENROLLMENT_CERT]);
    }

    public function test_converting_the_same_admission_twice_does_not_duplicate_documents(): void
    {
        $application = AdmissionApplication::create([
            'application_no' => 'ADM-TEST-0002',
            'name_en'        => 'Converted Twice',
            'gender'         => 'male',
            'status'         => 'accepted',
        ]);
        $admin = $this->makeAdmin();

        $service = app(AdmissionService::class);
        $student1 = $service->convertToStudent($application, $admin->id);
        $student2 = $service->convertToStudent($application->fresh(), $admin->id); // idempotent no-op per AdmissionService itself

        $this->assertEquals($student1->id, $student2->id);
        $this->assertEquals(1, IssuedDocument::where('student_id', $student1->id)->where('type', IssuedDocument::TYPE_ID_CARD)->count());
    }

    // ── Promotion (graduate / withdraw) ──────────────────────────────────────────

    public function test_promoting_a_student_to_graduated_auto_issues_a_graduation_certificate(): void
    {
        $fromYear = $this->makeYear('Year 2026', true);
        $toYear   = $this->makeYear('Year 2027');
        $grade12  = $this->makeClass('Grade 12'); // no next class → graduates
        $section  = $this->makeSection($grade12);

        $student = $this->makeStudent('enrolled');
        $this->enroll($student, $section, $fromYear);
        $this->makeAnnualResult($student, $section, $fromYear, 'pass');

        app(PromotionService::class)->execute($fromYear, $toYear);

        $cert = IssuedDocument::where('student_id', $student->id)->where('type', IssuedDocument::TYPE_GRADUATION_CERT)->first();
        $this->assertNotNull($cert);
        $this->assertMatchesRegularExpression('/^GRAD-\d{4}-\d{5}$/', $cert->number);
    }

    public function test_rerunning_promotion_does_not_duplicate_the_graduation_certificate(): void
    {
        $fromYear = $this->makeYear('Year 2026', true);
        $toYear   = $this->makeYear('Year 2027');
        $grade12  = $this->makeClass('Grade 12');
        $section  = $this->makeSection($grade12);

        $student = $this->makeStudent('enrolled');
        $this->enroll($student, $section, $fromYear);
        $this->makeAnnualResult($student, $section, $fromYear, 'pass');

        $service = app(PromotionService::class);
        $service->execute($fromYear, $toYear);
        $countAfterFirst = IssuedDocument::where('student_id', $student->id)->where('type', IssuedDocument::TYPE_GRADUATION_CERT)->count();

        // Re-run: the student is already 'graduated' but still has a fromYear
        // section row and no toYear row, so preview() re-selects them — this
        // is exactly the scenario the idempotency guard exists for.
        $service->execute($fromYear, $toYear);
        $countAfterSecond = IssuedDocument::where('student_id', $student->id)->where('type', IssuedDocument::TYPE_GRADUATION_CERT)->count();

        $this->assertEquals(1, $countAfterFirst);
        $this->assertEquals(1, $countAfterSecond);
    }

    public function test_promoting_a_student_to_withdrawn_auto_issues_a_leaving_certificate(): void
    {
        $fromYear = $this->makeYear('Year 2026', true);
        $toYear   = $this->makeYear('Year 2027');
        $class    = $this->makeClass('Grade 5');
        $section  = $this->makeSection($class);

        $student = $this->makeStudent('enrolled');
        $this->enroll($student, $section, $fromYear);

        app(PromotionService::class)->execute($fromYear, $toYear, [$student->id => 'withdraw']);

        $cert = IssuedDocument::where('student_id', $student->id)->where('type', IssuedDocument::TYPE_LEAVING_CERT)->first();
        $this->assertNotNull($cert);
        $this->assertMatchesRegularExpression('/^LEAVE-\d{4}-\d{5}$/', $cert->number);
    }

    // ── Manual status edit ───────────────────────────────────────────────────────

    public function test_manually_editing_status_to_transferred_auto_issues_a_leaving_certificate(): void
    {
        $student = $this->makeStudent('enrolled');

        $this->actingAs($this->makeAdmin())
            ->put(route('students.update', $student), [
                'name_en' => $student->name_en, 'gender' => 'male', 'status' => 'transferred',
            ])
            ->assertRedirect(route('students.show', $student));

        $this->assertDatabaseHas('issued_documents', ['student_id' => $student->id, 'type' => IssuedDocument::TYPE_LEAVING_CERT]);
    }

    public function test_manually_editing_an_unrelated_field_does_not_reissue_documents(): void
    {
        $student = $this->makeStudent('enrolled');
        $countBefore = IssuedDocument::where('student_id', $student->id)->count();

        $this->actingAs($this->makeAdmin())
            ->put(route('students.update', $student), [
                'name_en' => 'Renamed Student', 'gender' => 'male', 'status' => 'enrolled', 'address' => 'New Address',
            ])
            ->assertRedirect();

        $this->assertEquals($countBefore, IssuedDocument::where('student_id', $student->id)->count());
    }

    // ── Staff hire ───────────────────────────────────────────────────────────────

    public function test_a_staff_hire_auto_issues_their_id_card(): void
    {
        $staff = app(StaffService::class)->store([
            'name'  => 'New Hire',
            'email' => 'newhire-' . uniqid() . '@kia.edu.kh',
            'role'  => 'teacher',
        ]);

        $this->assertDatabaseHas('issued_documents', [
            'staff_id' => $staff->id, 'type' => IssuedDocument::TYPE_ID_CARD, 'number' => null,
        ]);
    }

    // ── The core Step-0 regression: numbers must be stable across repeat access ──

    public function test_downloading_the_same_certificate_twice_returns_the_same_number(): void
    {
        $student = $this->makeStudent('enrolled');
        $admin   = $this->makeAdmin();
        $query   = fn () => IssuedDocument::where('student_id', $student->id)->where('type', IssuedDocument::TYPE_ENROLLMENT_CERT);

        $this->actingAs($admin)->get(route('certificates.enrollment.pdf', $student))->assertOk();
        $numberAfterFirst = $query()->value('number');

        $this->actingAs($admin)->get(route('certificates.enrollment.pdf', $student))->assertOk();
        $numberAfterSecond = $query()->value('number');

        // Before this feature, every request burned a fresh sequence number —
        // downloading twice would silently produce two different numbers.
        $this->assertNotNull($numberAfterFirst);
        $this->assertEquals($numberAfterFirst, $numberAfterSecond);
        $this->assertEquals(1, $query()->count());
    }

    public function test_previewing_and_downloading_a_certificate_show_the_same_number(): void
    {
        $student = $this->makeStudent('enrolled');
        $admin   = $this->makeAdmin();

        $preview = $this->actingAs($admin)->get(route('certificates.enrollment', $student))->assertOk();
        $certNo  = IssuedDocument::where('student_id', $student->id)->where('type', IssuedDocument::TYPE_ENROLLMENT_CERT)->value('number');

        $preview->assertSee($certNo);
        $this->actingAs($admin)->get(route('certificates.enrollment.pdf', $student));

        // Still exactly one row, same number — preview and PDF share it.
        $this->assertEquals(1, IssuedDocument::where('student_id', $student->id)->where('type', IssuedDocument::TYPE_ENROLLMENT_CERT)->count());
        $this->assertEquals($certNo, IssuedDocument::where('student_id', $student->id)->where('type', IssuedDocument::TYPE_ENROLLMENT_CERT)->value('number'));
    }

    public function test_viewing_an_id_card_repeatedly_does_not_create_duplicate_records(): void
    {
        $student = $this->makeStudent('enrolled');
        $admin   = $this->makeAdmin();

        $this->actingAs($admin)->get(route('id-cards.student.show', $student));
        $this->actingAs($admin)->get(route('id-cards.student.pdf', $student));
        $this->actingAs($admin)->get(route('id-cards.student.show', $student));

        $this->assertEquals(1, IssuedDocument::where('student_id', $student->id)->where('type', IssuedDocument::TYPE_ID_CARD)->count());
    }

    // ── Documents section on the profile pages ──────────────────────────────────

    public function test_student_profile_shows_documents_with_a_download_link(): void
    {
        $student = $this->makeStudent('enrolled');

        $html = $this->actingAs($this->makeAdmin())
            ->get(route('students.show', $student))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(__('documents.id_card'), $html);
        $this->assertStringContainsString(__('documents.cert_enrollment'), $html);
        $this->assertStringContainsString(route('id-cards.student.pdf', $student), $html);
        $this->assertStringContainsString(route('certificates.enrollment.pdf', $student), $html);
    }

    public function test_parent_sees_their_childs_documents(): void
    {
        $student = $this->makeStudent('enrolled');
        $parent  = User::factory()->create(['status' => 'active']);
        $parent->assignRole('parent');
        $parent->wards()->attach($student->id, ['relation' => 'parent', 'is_primary' => true]);

        $html = $this->actingAs($parent)
            ->get(route('parent.child.show', $student))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(__('documents.id_card'), $html);
        // Parents don't hold certificates.issue — no working download link for the cert row.
        $this->assertStringNotContainsString(route('certificates.enrollment.pdf', $student), $html);
    }

    public function test_staff_profile_shows_the_auto_issued_id_card(): void
    {
        $staff = app(StaffService::class)->store([
            'name' => 'Card Holder', 'email' => 'cardholder-' . uniqid() . '@kia.edu.kh', 'role' => 'teacher',
        ]);

        $html = $this->actingAs($this->makeAdmin())
            ->get(route('staff.show', $staff))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(__('documents.id_card'), $html);
        $this->assertStringContainsString(route('id-cards.staff.pdf', $staff), $html);
    }
}
