<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);
        return $user;
    }

    private function makeStudent(): Student
    {
        return Student::create([
            'student_code' => 'S-' . uniqid(),
            'name_en'      => 'Test Student',
            'name_km'      => null,
            'gender'       => 'male',
            'status'       => 'enrolled',
        ]);
    }

    private function linkParent(User $parent, Student $student): void
    {
        DB::table('student_guardian')->insert([
            'student_id' => $student->id, 'guardian_id' => $parent->id,
            'relation' => 'parent', 'is_primary' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function makeTeacherOfSection(Student $student): User
    {
        $year = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::create(['name' => 'Y', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]);
        $class = SchoolClass::create(['name' => 'Grade '.uniqid(), 'level' => 'High', 'capacity' => 30]);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);
        $section->students()->attach($student->id, ['academic_year_id' => $year->id]);

        $teacherUser = User::factory()->create(['status' => 'active']);
        $teacherUser->assignRole('teacher');
        $staff = Staff::create([
            'user_id' => $teacherUser->id, 'staff_code' => 'ST-'.uniqid(), 'position' => 'Teacher', 'department' => 'Academics',
        ]);
        $section->update(['class_teacher_id' => $staff->id]);

        return $teacherUser;
    }

    // ── Upload ───────────────────────────────────────────────────────────────

    public function test_receptionist_can_upload_a_document(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($this->makeUser('receptionist'))
            ->post(route('student-documents.store', $student), [
                'label' => 'Birth Certificate',
                'file'  => UploadedFile::fake()->create('bc.pdf', 500, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('student_documents', [
            'student_id' => $student->id,
            'label'      => 'Birth Certificate',
        ]);
    }

    public function test_teacher_cannot_upload_a_document(): void
    {
        // Teacher holds students.view but not students.edit.
        $student = $this->makeStudent();

        $this->actingAs($this->makeUser('teacher'))
            ->post(route('student-documents.store', $student), [
                'label' => 'Birth Certificate',
                'file'  => UploadedFile::fake()->create('bc.pdf', 500, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('student_documents', 0);
    }

    public function test_parent_cannot_upload_a_document(): void
    {
        $student = $this->makeStudent();
        $parent  = $this->makeUser('parent');
        $this->linkParent($parent, $student);

        $this->actingAs($parent)
            ->post(route('student-documents.store', $student), [
                'label' => 'Birth Certificate',
                'file'  => UploadedFile::fake()->create('bc.pdf', 500, 'application/pdf'),
            ])
            ->assertForbidden();
    }

    public function test_oversized_document_is_rejected(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($this->makeUser('admin'))
            ->post(route('student-documents.store', $student), [
                'label' => 'Big File',
                'file'  => UploadedFile::fake()->create('big.pdf', 6 * 1024, 'application/pdf'), // 6 MB > 5 MB limit
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_disallowed_file_type_is_rejected(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($this->makeUser('admin'))
            ->post(route('student-documents.store', $student), [
                'label' => 'Bad Type',
                'file'  => UploadedFile::fake()->create('script.exe', 100),
            ])
            ->assertSessionHasErrors('file');
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function test_admin_can_delete_a_document(): void
    {
        $student = $this->makeStudent();
        $doc = StudentDocument::create([
            'student_id' => $student->id, 'label' => 'Old File',
            'path' => 'students/documents/old.pdf', 'original_name' => 'old.pdf',
        ]);

        $this->actingAs($this->makeUser('admin'))
            ->delete(route('student-documents.destroy', $doc))
            ->assertRedirect();

        $this->assertDatabaseMissing('student_documents', ['id' => $doc->id]);
    }

    public function test_librarian_cannot_delete_a_document(): void
    {
        $student = $this->makeStudent();
        $doc = StudentDocument::create([
            'student_id' => $student->id, 'label' => 'Old File',
            'path' => 'students/documents/old.pdf', 'original_name' => 'old.pdf',
        ]);

        $this->actingAs($this->makeUser('librarian'))
            ->delete(route('student-documents.destroy', $doc))
            ->assertForbidden();

        $this->assertDatabaseHas('student_documents', ['id' => $doc->id]);
    }

    // ── Download / visibility ────────────────────────────────────────────────

    public function test_staff_with_students_view_can_download(): void
    {
        $student = $this->makeStudent();
        Storage::disk('local')->put('students/documents/x.pdf', 'contents');
        $doc = StudentDocument::create([
            'student_id' => $student->id, 'label' => 'X',
            'path' => 'students/documents/x.pdf', 'original_name' => 'x.pdf',
        ]);

        $this->actingAs($this->makeUser('accountant'))
            ->get(route('student-documents.download', $doc))
            ->assertOk();
    }

    public function test_own_parent_can_download(): void
    {
        $student = $this->makeStudent();
        $parent  = $this->makeUser('parent');
        $this->linkParent($parent, $student);

        Storage::disk('local')->put('students/documents/x.pdf', 'contents');
        $doc = StudentDocument::create([
            'student_id' => $student->id, 'label' => 'X',
            'path' => 'students/documents/x.pdf', 'original_name' => 'x.pdf',
        ]);

        $this->actingAs($parent)
            ->get(route('student-documents.download', $doc))
            ->assertOk();
    }

    public function test_unrelated_parent_cannot_download(): void
    {
        $student = $this->makeStudent();
        $otherParent = $this->makeUser('parent'); // NOT linked to $student

        Storage::disk('local')->put('students/documents/x.pdf', 'contents');
        $doc = StudentDocument::create([
            'student_id' => $student->id, 'label' => 'X',
            'path' => 'students/documents/x.pdf', 'original_name' => 'x.pdf',
        ]);

        $this->actingAs($otherParent)
            ->get(route('student-documents.download', $doc))
            ->assertForbidden();
    }

    public function test_role_with_no_student_access_cannot_download(): void
    {
        // librarian/receptionist hold students.view; a role that holds NEITHER
        // students.view NOR is a linked parent should be blocked. Every seeded
        // staff role happens to hold students.view except... none do — so we
        // simulate the "neither" case with an unlinked parent instead, which
        // is the only role that must be scoped by ownership rather than a flat permission.
        $student = $this->makeStudent();
        $studentUser = $this->makeUser('student'); // students hold no students.view either

        Storage::disk('local')->put('students/documents/x.pdf', 'contents');
        $doc = StudentDocument::create([
            'student_id' => $student->id, 'label' => 'X',
            'path' => 'students/documents/x.pdf', 'original_name' => 'x.pdf',
        ]);

        $this->actingAs($studentUser)
            ->get(route('student-documents.download', $doc))
            ->assertForbidden();
    }

    public function test_teacher_can_download_a_document_of_their_own_student(): void
    {
        $student = $this->makeStudent();
        $teacher = $this->makeTeacherOfSection($student);

        Storage::disk('local')->put('students/documents/x.pdf', 'contents');
        $doc = StudentDocument::create([
            'student_id' => $student->id, 'label' => 'X',
            'path' => 'students/documents/x.pdf', 'original_name' => 'x.pdf',
        ]);

        $this->actingAs($teacher)
            ->get(route('student-documents.download', $doc))
            ->assertOk();
    }

    public function test_teacher_cannot_download_a_document_of_a_student_not_theirs(): void
    {
        $student = $this->makeStudent(); // not attached to any section this teacher teaches
        $otherStudent = $this->makeStudent();
        $teacher = $this->makeTeacherOfSection($otherStudent);

        Storage::disk('local')->put('students/documents/x.pdf', 'contents');
        $doc = StudentDocument::create([
            'student_id' => $student->id, 'label' => 'X',
            'path' => 'students/documents/x.pdf', 'original_name' => 'x.pdf',
        ]);

        $this->actingAs($teacher)
            ->get(route('student-documents.download', $doc))
            ->assertForbidden();
    }
}
