<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\StaffDocument;
use App\Models\StaffQualification;
use App\Models\Subject;
use App\Models\Timetable;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeUser(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }

    private function makeStaff(string $role = 'teacher'): Staff
    {
        $user = $this->makeUser($role);

        return Staff::create([
            'user_id' => $user->id, 'staff_code' => 'ST-'.uniqid(),
            'position' => 'Teacher', 'department' => 'Academics',
        ]);
    }

    // ── Qualifications ───────────────────────────────────────────────────────

    public function test_admin_can_add_a_qualification(): void
    {
        $staff = $this->makeStaff();

        $response = $this->actingAs($this->makeUser('admin'))->post(route('staff-qualifications.store', $staff), [
            'degree' => 'B.Ed', 'institution' => 'RUPP', 'year' => 2018,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('staff_qualifications', [
            'staff_id' => $staff->id, 'degree' => 'B.Ed', 'institution' => 'RUPP', 'year' => 2018,
        ]);
    }

    public function test_qualifications_list_shows_newest_year_first(): void
    {
        $staff = $this->makeStaff();
        StaffQualification::create(['staff_id' => $staff->id, 'degree' => 'BA', 'institution' => 'X', 'year' => 2010]);
        StaffQualification::create(['staff_id' => $staff->id, 'degree' => 'MA', 'institution' => 'Y', 'year' => 2015]);

        $degrees = $staff->fresh()->qualifications->pluck('degree')->toArray();

        $this->assertEquals(['MA', 'BA'], $degrees);
    }

    public function test_admin_can_delete_a_qualification(): void
    {
        $staff = $this->makeStaff();
        $qualification = StaffQualification::create(['staff_id' => $staff->id, 'degree' => 'BA', 'institution' => 'X', 'year' => 2010]);

        $this->actingAs($this->makeUser('admin'))
            ->delete(route('staff-qualifications.destroy', $qualification))
            ->assertRedirect();

        $this->assertDatabaseMissing('staff_qualifications', ['id' => $qualification->id]);
    }

    public function test_teacher_cannot_add_a_qualification_for_another_staff_member(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($this->makeUser('teacher'))->post(route('staff-qualifications.store', $staff), [
            'degree' => 'BA', 'institution' => 'X', 'year' => 2010,
        ])->assertForbidden();
    }

    // ── CV / document upload (gated private-disk pattern) ───────────────────

    public function test_admin_can_upload_a_document(): void
    {
        $staff = $this->makeStaff();
        $file = UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf');

        $response = $this->actingAs($this->makeUser('admin'))->post(route('staff-documents.store', $staff), [
            'label' => 'CV', 'file' => $file,
        ]);

        $response->assertRedirect();
        $document = StaffDocument::where('staff_id', $staff->id)->firstOrFail();
        $this->assertEquals('CV', $document->label);
        Storage::disk('local')->assertExists($document->path);
    }

    public function test_staff_holding_staff_view_can_download_any_staff_document(): void
    {
        $staff = $this->makeStaff();
        $document = $this->uploadDocument($staff);

        $this->actingAs($this->makeUser('admin'))
            ->get(route('staff-documents.download', $document))
            ->assertOk();
    }

    public function test_staff_member_can_download_their_own_document(): void
    {
        $staff = $this->makeStaff('librarian'); // librarian lacks staff.view per RolePermissionSeeder
        $document = $this->uploadDocument($staff);

        $this->actingAs($staff->user)
            ->get(route('staff-documents.download', $document))
            ->assertOk();
    }

    public function test_unrelated_staff_member_cannot_download_someone_elses_document(): void
    {
        $staff = $this->makeStaff('librarian');
        $document = $this->uploadDocument($staff);

        $otherStaff = $this->makeStaff('librarian');

        $this->actingAs($otherStaff->user)
            ->get(route('staff-documents.download', $document))
            ->assertForbidden();
    }

    public function test_admin_can_delete_a_document(): void
    {
        $staff = $this->makeStaff();
        $document = $this->uploadDocument($staff);

        $this->actingAs($this->makeUser('admin'))
            ->delete(route('staff-documents.destroy', $document))
            ->assertRedirect();

        $this->assertDatabaseMissing('staff_documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($document->path);
    }

    private function uploadDocument(Staff $staff): StaffDocument
    {
        $file = UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf');
        $this->actingAs($this->makeUser('admin'))->post(route('staff-documents.store', $staff), [
            'label' => 'CV', 'file' => $file,
        ]);

        return StaffDocument::where('staff_id', $staff->id)->firstOrFail();
    }

    // ── Workload report — reconciliation against seeded Timetable rows ──────

    public function test_workload_summary_matches_seeded_timetable_rows_exactly(): void
    {
        $staff = $this->makeStaff();
        $subject = Subject::create(['name_en' => 'Math', 'name_km' => 'M', 'code' => 'M-'.uniqid(), 'full_mark' => 100, 'coefficient' => 1]);

        $classA = SchoolClass::create(['name' => 'Grade A-'.uniqid(), 'level' => 'High', 'capacity' => 30]);
        $sectionA = Section::create(['school_class_id' => $classA->id, 'name' => 'A']);
        $classB = SchoolClass::create(['name' => 'Grade B-'.uniqid(), 'level' => 'High', 'capacity' => 30]);
        $sectionB = Section::create(['school_class_id' => $classB->id, 'name' => 'A']);

        // 3 periods in section A, 2 periods in section B = 5 periods/week across 2 sections.
        foreach ([1, 2, 3] as $period) {
            Timetable::create([
                'section_id' => $sectionA->id, 'subject_id' => $subject->id, 'teacher_id' => $staff->id,
                'day' => 'monday', 'period' => $period, 'start_time' => '07:00', 'end_time' => '08:00',
            ]);
        }
        foreach ([1, 2] as $period) {
            Timetable::create([
                'section_id' => $sectionB->id, 'subject_id' => $subject->id, 'teacher_id' => $staff->id,
                'day' => 'tuesday', 'period' => $period, 'start_time' => '07:00', 'end_time' => '08:00',
            ]);
        }

        $response = $this->actingAs($this->makeUser('admin'))
            ->get(route('staff.teaching-schedule', $staff));

        $response->assertOk();
        $this->assertEquals(5, $response->viewData('totalPeriods'));
        $this->assertEquals(2, $response->viewData('sectionsTaught'));
    }

    public function test_workload_summary_is_zero_for_a_teacher_with_no_timetable_slots(): void
    {
        $staff = $this->makeStaff();

        $response = $this->actingAs($this->makeUser('admin'))->get(route('staff.teaching-schedule', $staff));

        $this->assertEquals(0, $response->viewData('totalPeriods'));
        $this->assertEquals(0, $response->viewData('sectionsTaught'));
    }
}
