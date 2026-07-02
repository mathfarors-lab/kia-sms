<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\DocumentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoPrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function makeAdmin(): User
    {
        $u = User::factory()->create();
        $u->assignRole('admin');
        return $u;
    }

    private function makeTeacher(): User
    {
        $u = User::factory()->create();
        $u->assignRole('teacher');
        return $u;
    }

    private function makeParent(): User
    {
        $u = User::factory()->create();
        $u->assignRole('parent');
        return $u;
    }

    private function makeStudentUser(): User
    {
        $u = User::factory()->create();
        $u->assignRole('student');
        return $u;
    }

    private function makeStudent(?User $linkedUser = null): Student
    {
        return Student::create([
            'user_id'      => $linkedUser?->id,
            'student_code' => 'TEST-' . rand(1000, 9999),
            'name_en'      => 'Test Student',
            'gender'       => 'male',
            'status'       => 'enrolled',
        ]);
    }

    /** Put a fake JPEG on the private (local) disk and return the path. */
    private function putFakePhoto(string $path = 'students/photos/fake.jpg'): string
    {
        Storage::disk('local')->put($path, 'fake-image-bytes');
        return $path;
    }

    // ---------------------------------------------------------------
    // 1. Unauthenticated → redirect to login
    // ---------------------------------------------------------------

    public function test_unauthenticated_cannot_view_student_photo(): void
    {
        $student = $this->makeStudent();

        $this->get(route('students.photo', $student))
             ->assertRedirect(route('login'));
    }

    // ---------------------------------------------------------------
    // 2. Parent — own child vs stranger
    // ---------------------------------------------------------------

    public function test_parent_can_view_own_childs_photo(): void
    {
        $parent  = $this->makeParent();
        $student = $this->makeStudent();
        $photo   = $this->putFakePhoto();
        $student->update(['photo' => $photo]);

        // Link as ward
        $parent->wards()->attach($student->id, ['relation' => 'parent', 'is_primary' => true]);

        $this->actingAs($parent)
             ->get(route('students.photo', $student))
             ->assertStatus(200);
    }

    public function test_parent_cannot_view_another_childs_photo(): void
    {
        $parent  = $this->makeParent();
        $student = $this->makeStudent();   // not linked to parent
        $photo   = $this->putFakePhoto();
        $student->update(['photo' => $photo]);

        $this->actingAs($parent)
             ->get(route('students.photo', $student))
             ->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // 3. Student — own vs another
    // ---------------------------------------------------------------

    public function test_student_can_view_own_photo(): void
    {
        $userAcct = $this->makeStudentUser();
        $student  = $this->makeStudent($userAcct);
        $photo    = $this->putFakePhoto();
        $student->update(['photo' => $photo]);

        $this->actingAs($userAcct)
             ->get(route('students.photo', $student))
             ->assertStatus(200);
    }

    public function test_student_cannot_view_another_students_photo(): void
    {
        $userAcct       = $this->makeStudentUser();
        $otherStudent   = $this->makeStudent();   // different user_id
        $photo          = $this->putFakePhoto();
        $otherStudent->update(['photo' => $photo]);

        $this->actingAs($userAcct)
             ->get(route('students.photo', $otherStudent))
             ->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // 4. Admin / teacher see any photo
    // ---------------------------------------------------------------

    public function test_admin_can_view_any_student_photo(): void
    {
        $admin   = $this->makeAdmin();
        $student = $this->makeStudent();
        $photo   = $this->putFakePhoto();
        $student->update(['photo' => $photo]);

        $this->actingAs($admin)
             ->get(route('students.photo', $student))
             ->assertStatus(200);
    }

    public function test_teacher_can_view_any_student_photo(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent();
        $photo   = $this->putFakePhoto();
        $student->update(['photo' => $photo]);

        $this->actingAs($teacher)
             ->get(route('students.photo', $student))
             ->assertStatus(200);
    }

    // ---------------------------------------------------------------
    // 5. Placeholder when no photo
    // ---------------------------------------------------------------

    public function test_returns_svg_placeholder_when_no_photo(): void
    {
        $admin   = $this->makeAdmin();
        $student = $this->makeStudent();   // photo = null

        $response = $this->actingAs($admin)
                         ->get(route('students.photo', $student));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/svg+xml');
    }

    // ---------------------------------------------------------------
    // 6. Newly uploaded photo lands on private disk (not public)
    // ---------------------------------------------------------------

    public function test_upload_stores_photo_on_private_disk_with_generated_name(): void
    {
        $admin   = $this->makeAdmin();
        $student = $this->makeStudent();

        $file = UploadedFile::fake()->image('student.jpg');

        $this->actingAs($admin)->put(route('students.update', $student), [
            'name_en'  => $student->name_en,
            'gender'   => $student->gender,
            'status'   => $student->status,
            'photo'    => $file,
        ]);

        $student->refresh();
        $this->assertNotNull($student->photo);

        // Must be on the private disk
        Storage::disk('local')->assertExists($student->photo);

        // Must NOT be on the public disk
        Storage::disk('public')->assertMissing($student->photo);

        // Generated name: must NOT match the original user-supplied filename
        $this->assertStringNotContainsString('student.jpg', $student->photo);
    }

    // ---------------------------------------------------------------
    // 7. ID card still reads from private disk
    // ---------------------------------------------------------------

    public function test_document_service_reads_photo_from_private_disk(): void
    {
        $path = 'students/photos/test.jpg';
        // Minimal valid 1×1 pixel JPEG
        $jpegBytes = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAARC'.'AABAAEDAS'.'IAAR'.'EAAQA'.'H/8QAFAAB'.'AAAAA'.'AAAAAAAAAA'.'AAAAA'.'AD/xAAU'.'EAEAAAA'.'AAAA'.'AAAAAA'.'AAAA'.'D/xAAU'.'AQEA'.'AAAA'.'AAAA'.'AAAA'.'AAAA'.'D/x'.'AAUQ'.'AQAA'.'AAAA'.'AAAA'.'AAAA'.'AAAD/2gAMAwEAAhEDEQA/ACwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAB/9k=');
        Storage::disk('local')->put($path, $jpegBytes);

        $service = new DocumentService();
        $dataUri = $service->photoDataUri($path);

        $this->assertNotNull($dataUri);
        $this->assertStringStartsWith('data:image/', $dataUri);
    }

    // ---------------------------------------------------------------
    // 8. Migration command: moves file from public to private, idempotent
    // ---------------------------------------------------------------

    public function test_migration_command_moves_photo_to_private_disk(): void
    {
        // Simulate an existing student whose photo is on the public disk
        $student = $this->makeStudent();
        $path    = 'students/photos/legacy.jpg';
        Storage::disk('public')->put($path, 'legacy-image-bytes');
        $student->update(['photo' => $path]);

        // Before: public=yes, private=no
        Storage::disk('public')->assertExists($path);
        Storage::disk('local')->assertMissing($path);

        $this->artisan('photos:migrate-to-private')->assertExitCode(0);

        // After: public=no, private=yes
        Storage::disk('public')->assertMissing($path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_migration_command_is_idempotent(): void
    {
        $student = $this->makeStudent();
        $path    = 'students/photos/already-private.jpg';
        Storage::disk('local')->put($path, 'already-migrated');
        $student->update(['photo' => $path]);

        // Running the command twice must not error
        $this->artisan('photos:migrate-to-private')->assertExitCode(0);
        $this->artisan('photos:migrate-to-private')->assertExitCode(0);

        Storage::disk('local')->assertExists($path);
    }

    // ---------------------------------------------------------------
    // 9. No public Storage URL used in views
    // ---------------------------------------------------------------

    public function test_student_show_view_does_not_expose_storage_url(): void
    {
        $admin   = $this->makeAdmin();
        $student = $this->makeStudent();
        $photo   = $this->putFakePhoto();
        $student->update(['photo' => $photo]);

        $html = $this->actingAs($admin)
                     ->get(route('students.show', $student))
                     ->assertStatus(200)
                     ->getContent();

        $this->assertStringNotContainsString('/storage/students/photos', $html);
        $this->assertStringContainsString(route('students.photo', $student), $html);
    }
}
