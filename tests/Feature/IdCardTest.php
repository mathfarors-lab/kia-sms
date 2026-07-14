<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\IssuedDocument;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\StaffService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdCardTest extends TestCase
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
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    private function makeYear(): AcademicYear
    {
        return AcademicYear::create([
            'name' => 'Test Year', 'start_date' => '2026-01-01',
            'end_date' => '2026-12-31', 'is_active' => true,
        ]);
    }

    private function makeStudentUser(array $overrides = []): array
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('student');
        $student = Student::create(array_merge([
            'user_id'      => $user->id,
            'name_en'      => 'Student-' . uniqid(),
            'name_km'      => null,
            'student_code' => 'S-' . uniqid(),
            'gender'       => 'female',
            'photo'        => null,
            'status'       => 'enrolled',
        ], $overrides));
        return [$user, $student];
    }

    private function attachToSection(Student $student, AcademicYear $year): Section
    {
        static $i = 0;
        $i++;
        $class   = SchoolClass::create(['name' => "Grade $i", 'level' => 'High', 'capacity' => 30]);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);
        $section->students()->attach($student->id, ['academic_year_id' => $year->id]);
        return $section;
    }

    private function makeStaff(string $role = 'teacher'): Staff
    {
        return app(StaffService::class)->store([
            'name' => 'Staff-' . uniqid(), 'email' => 'staff-' . uniqid() . '@kia.edu.kh', 'role' => $role,
        ]);
    }

    // ── Tests ─────────────────────────────────────────────────────────────────────

    /** Admin can view student ID card HTML preview. */
    public function test_admin_can_view_student_id_card(): void
    {
        $year = $this->makeYear();
        [$user, $student] = $this->makeStudentUser();
        $this->attachToSection($student, $year);

        $this->actingAs($this->makeAdmin())
            ->get(route('id-cards.student.show', $student))
            ->assertOk()
            ->assertSee($student->student_code);
    }

    /** Student ID card QR payload is exactly the student_code (nothing else). */
    public function test_student_id_card_qr_encodes_only_student_code(): void
    {
        $year = $this->makeYear();
        [$user, $student] = $this->makeStudentUser();
        $this->attachToSection($student, $year);

        $this->actingAs($user)
            ->get(route('id-cards.student.show', $student))
            ->assertOk()
            ->assertSee('data-qr-payload="' . $student->student_code . '"', false);
    }

    /** ID card renders without photo (placeholder path). */
    public function test_id_card_renders_without_photo(): void
    {
        $year = $this->makeYear();
        [$user, $student] = $this->makeStudentUser(['photo' => null]);
        $this->attachToSection($student, $year);

        $this->actingAs($user)
            ->get(route('id-cards.student.show', $student))
            ->assertOk()
            ->assertSee($student->student_code);
    }

    /** ID card embeds a base64 photo when one is set. */
    public function test_id_card_renders_with_photo(): void
    {
        Storage::fake('public');
        $photoPath = 'photos/test-student.png';
        // Tiny 1x1 red PNG
        Storage::disk('public')->put(
            $photoPath,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI6QAAAABJRU5ErkJggg==')
        );

        $year = $this->makeYear();
        [$user, $student] = $this->makeStudentUser(['photo' => $photoPath]);
        $this->attachToSection($student, $year);

        $this->actingAs($user)
            ->get(route('id-cards.student.show', $student))
            ->assertOk()
            ->assertSee('data:image/', false);
    }

    /** Student cannot view another student's ID card. */
    public function test_student_cannot_view_another_students_id_card(): void
    {
        $year = $this->makeYear();
        [$userA, $studentA] = $this->makeStudentUser();
        [$userB, $studentB] = $this->makeStudentUser();
        $this->attachToSection($studentA, $year);

        $this->actingAs($userB)
            ->get(route('id-cards.student.show', $studentA))
            ->assertForbidden();
    }

    /** Batch preview contains all students in the section. */
    public function test_batch_preview_contains_all_section_students(): void
    {
        $year    = $this->makeYear();
        $class   = SchoolClass::create(['name' => 'Grade Batch', 'level' => 'High', 'capacity' => 30]);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'Batch']);

        [, $s1] = $this->makeStudentUser();
        [, $s2] = $this->makeStudentUser();
        [, $s3] = $this->makeStudentUser();

        foreach ([$s1, $s2, $s3] as $s) {
            $section->students()->attach($s->id, ['academic_year_id' => $year->id]);
        }

        $this->actingAs($this->makeAdmin())
            ->get(route('id-cards.batch.preview', $section))
            ->assertOk()
            ->assertSee($s1->student_code)
            ->assertSee($s2->student_code)
            ->assertSee($s3->student_code);
    }

    /** Batch preview has data-student-code attributes for each student. */
    public function test_batch_preview_has_data_student_code_attributes(): void
    {
        $year    = $this->makeYear();
        $class   = SchoolClass::create(['name' => 'Grade Attr', 'level' => 'High', 'capacity' => 30]);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'Attr']);

        [, $s1] = $this->makeStudentUser();
        [, $s2] = $this->makeStudentUser();

        foreach ([$s1, $s2] as $s) {
            $section->students()->attach($s->id, ['academic_year_id' => $year->id]);
        }

        $this->actingAs($this->makeAdmin())
            ->get(route('id-cards.batch.preview', $section))
            ->assertOk()
            ->assertSee('data-student-code="' . $s1->student_code . '"', false)
            ->assertSee('data-student-code="' . $s2->student_code . '"', false);
    }

    /** Teacher can generate ID cards (has id-cards.generate permission). */
    public function test_teacher_can_view_student_id_card(): void
    {
        $year = $this->makeYear();
        [, $student] = $this->makeStudentUser();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)
            ->get(route('id-cards.student.show', $student))
            ->assertOk();
    }

    /** Viewing a student ID card auto-backfills an IssuedDocument record. */
    public function test_viewing_student_id_card_records_issuance(): void
    {
        $year = $this->makeYear();
        [, $student] = $this->makeStudentUser();
        $this->attachToSection($student, $year);

        $this->actingAs($this->makeAdmin())->get(route('id-cards.student.show', $student));

        $this->assertDatabaseHas('issued_documents', [
            'student_id' => $student->id, 'type' => IssuedDocument::TYPE_ID_CARD,
        ]);
    }

    // ── Staff card authorization (regression: this used to have no ownership check) ─

    public function test_staff_member_can_download_their_own_id_card(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($staff->user)
            ->get(route('id-cards.staff.pdf', $staff))
            ->assertOk();
    }

    public function test_staff_member_cannot_download_another_staffs_id_card(): void
    {
        $staffA = $this->makeStaff();
        $staffB = $this->makeStaff();

        $this->actingAs($staffA->user)
            ->get(route('id-cards.staff.pdf', $staffB))
            ->assertForbidden();
    }

    /** The actual bug: a student holds id-cards.generate for their OWN card, which used to be enough to pass pdfStaff() too. */
    public function test_student_cannot_download_a_staff_id_card(): void
    {
        $staff = $this->makeStaff();
        [$studentUser] = $this->makeStudentUser();

        $this->actingAs($studentUser)
            ->get(route('id-cards.staff.pdf', $staff))
            ->assertForbidden();
    }

    public function test_parent_cannot_download_a_staff_id_card(): void
    {
        $staff  = $this->makeStaff();
        $parent = User::factory()->create(['status' => 'active']);
        $parent->assignRole('parent');

        $this->actingAs($parent)
            ->get(route('id-cards.staff.pdf', $staff))
            ->assertForbidden();
    }

    public function test_admin_can_download_any_staff_id_card(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($this->makeAdmin())
            ->get(route('id-cards.staff.pdf', $staff))
            ->assertOk();
    }

    public function test_receptionist_can_download_any_staff_id_card(): void
    {
        $staff = $this->makeStaff();
        $receptionist = User::factory()->create(['status' => 'active']);
        $receptionist->assignRole('receptionist');

        $this->actingAs($receptionist)
            ->get(route('id-cards.staff.pdf', $staff))
            ->assertOk();
    }
}
