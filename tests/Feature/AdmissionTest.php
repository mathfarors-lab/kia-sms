<?php

namespace Tests\Feature;

use App\Models\AdmissionApplication;
use App\Models\Student;
use App\Models\User;
use App\Services\AdmissionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdmissionTest extends TestCase
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

    private function makeApplication(string $status = 'applied'): AdmissionApplication
    {
        return AdmissionApplication::create([
            'application_no' => 'ADM-26-' . str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'name_en'        => 'Applicant-' . uniqid(),
            'name_km'        => 'បេក្ខជន',
            'gender'         => 'female',
            'status'         => $status,
        ]);
    }

    // ── Access control ───────────────────────────────────────────────────────

    public function test_receptionist_and_principal_can_view_admissions_index(): void
    {
        foreach (['receptionist', 'principal', 'admin'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->get(route('admissions.index'))
                ->assertOk();
        }
    }

    public function test_teacher_and_student_cannot_view_admissions(): void
    {
        foreach (['teacher', 'student', 'parent', 'accountant', 'librarian'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->get(route('admissions.index'))
                ->assertForbidden();
        }
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public function test_receptionist_can_create_application_with_document_on_private_disk(): void
    {
        $receptionist = $this->makeUser('receptionist');

        $this->actingAs($receptionist)->post(route('admissions.store'), [
            'name_en'        => 'New Applicant',
            'name_km'        => 'បេក្ខជនថ្មី',
            'gender'         => 'male',
            'date_of_birth'  => '2015-04-01',
            'guardian_name'  => 'Guardian Dad',
            'guardian_phone' => '012 777 888',
            'status'         => 'applied',
            'document'       => UploadedFile::fake()->create('birth-cert.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $app = AdmissionApplication::where('name_en', 'New Applicant')->firstOrFail();

        $this->assertMatchesRegularExpression('/^ADM-\d{2}-\d{4}$/', $app->application_no);
        $this->assertEquals('applied', $app->status);
        $this->assertNotNull($app->document_path);
        Storage::disk('local')->assertExists($app->document_path);
        $this->assertEquals('birth-cert.pdf', $app->document_original_name);
    }

    public function test_application_numbers_increment(): void
    {
        $service = app(AdmissionService::class);
        $first  = $service->generateNumber();
        AdmissionApplication::create(['application_no' => $first, 'name_en' => 'A', 'gender' => 'male', 'status' => 'enquiry']);
        $second = $service->generateNumber();

        $this->assertNotEquals($first, $second);
        $this->assertEquals((int) substr($first, -4) + 1, (int) substr($second, -4));
    }

    // ── Pipeline transitions ─────────────────────────────────────────────────

    public function test_status_can_move_through_pipeline(): void
    {
        $admin = $this->makeUser('admin');
        $app   = $this->makeApplication('applied');

        $this->actingAs($admin)->post(route('admissions.status', $app), ['status' => 'under_review'])->assertRedirect();
        $this->assertEquals('under_review', $app->fresh()->status);

        $this->actingAs($admin)->post(route('admissions.status', $app), ['status' => 'accepted'])->assertRedirect();
        $this->assertEquals('accepted', $app->fresh()->status);
        $this->assertEquals($admin->id, $app->fresh()->reviewed_by);
    }

    // ── Conversion ───────────────────────────────────────────────────────────

    public function test_accepted_application_converts_to_enrolled_student(): void
    {
        $admin = $this->makeUser('admin');
        $app   = $this->makeApplication('accepted');

        $this->actingAs($admin)
            ->post(route('admissions.convert', $app))
            ->assertRedirect();

        $app->refresh();
        $this->assertEquals('converted', $app->status);
        $this->assertNotNull($app->student_id);

        $student = $app->student;
        $this->assertEquals('enrolled', $student->status);
        $this->assertEquals($app->name_en, $student->name_en);
        $this->assertEquals($app->name_km, $student->name_km);
        $this->assertNotEmpty($student->student_code); // generated by StudentService
    }

    public function test_conversion_is_idempotent(): void
    {
        $admin = $this->makeUser('admin');
        $app   = $this->makeApplication('accepted');

        $service  = app(AdmissionService::class);
        $student1 = $service->convertToStudent($app, $admin->id);
        $student2 = $service->convertToStudent($app->fresh(), $admin->id);

        $this->assertEquals($student1->id, $student2->id);
        $this->assertEquals(1, Student::where('name_en', $app->name_en)->count());
    }

    public function test_non_accepted_application_cannot_convert(): void
    {
        $admin = $this->makeUser('admin');
        $app   = $this->makeApplication('under_review');

        $this->expectException(ValidationException::class);
        app(AdmissionService::class)->convertToStudent($app, $admin->id);
    }

    public function test_converted_application_is_locked_from_edits_and_status_changes(): void
    {
        $admin = $this->makeUser('admin');
        $app   = $this->makeApplication('accepted');
        app(AdmissionService::class)->convertToStudent($app, $admin->id);
        $app->refresh();

        // Status change bounces.
        $this->actingAs($admin)->post(route('admissions.status', $app), ['status' => 'rejected']);
        $this->assertEquals('converted', $app->fresh()->status);

        // Update bounces.
        $this->actingAs($admin)->patch(route('admissions.update', $app), [
            'name_en' => 'Should Not Change', 'gender' => 'male',
        ]);
        $this->assertNotEquals('Should Not Change', $app->fresh()->name_en);
    }

    // ── Document download gating ─────────────────────────────────────────────

    public function test_document_download_gated_by_view_permission(): void
    {
        $app  = $this->makeApplication();
        $path = UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')->store('admissions/documents', 'local');
        $app->update(['document_path' => $path, 'document_original_name' => 'doc.pdf']);

        $this->actingAs($this->makeUser('receptionist'))
            ->get(route('admissions.document', $app))
            ->assertOk();

        $this->actingAs($this->makeUser('teacher'))
            ->get(route('admissions.document', $app))
            ->assertForbidden();
    }

    // ── Sidebar reachability ─────────────────────────────────────────────────

    public function test_sidebar_shows_admissions_to_receptionist_and_principal_not_teacher(): void
    {
        foreach (['receptionist', 'principal'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->followingRedirects()->get(route('dashboard'))
                ->assertSee(route('admissions.index'), false);
        }

        $this->actingAs($this->makeUser('teacher'))
            ->followingRedirects()->get(route('dashboard'))
            ->assertDontSee(route('admissions.index'), false);
    }
}
