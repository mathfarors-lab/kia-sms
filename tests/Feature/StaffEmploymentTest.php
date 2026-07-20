<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\StaffDocument;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffEmploymentTest extends TestCase
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

    private function makeStaff(string $role = 'teacher'): Staff
    {
        $user = $this->makeUser($role);

        return Staff::create([
            'user_id' => $user->id, 'staff_code' => 'ST-'.uniqid(),
            'position' => 'Teacher', 'department' => 'Academics', 'joined_at' => '2024-01-01',
        ]);
    }

    private function updatePayload(Staff $staff, array $overrides = []): array
    {
        return array_merge([
            'name' => $staff->user->name,
            'email' => $staff->user->email,
            'role' => $staff->user->getRoleNames()->first(),
            'joined_at' => $staff->joined_at?->toDateString() ?? '2024-01-01',
        ], $overrides);
    }

    // ── Contract date validation ─────────────────────────────────────────────

    public function test_contract_end_date_after_joined_at_is_accepted(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($this->makeUser('admin'))
            ->patch(route('staff.update', $staff), $this->updatePayload($staff, [
                'contract_type' => 'full_time',
                'contract_end_date' => '2026-12-31',
            ]))
            ->assertRedirect();

        $staff->refresh();
        $this->assertEquals('full_time', $staff->contract_type);
        $this->assertEquals('2026-12-31', $staff->contract_end_date->toDateString());
    }

    public function test_contract_end_date_before_joined_at_is_rejected(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($this->makeUser('admin'))
            ->patch(route('staff.update', $staff), $this->updatePayload($staff, [
                'contract_end_date' => '2023-01-01', // before joined_at (2024-01-01)
            ]))
            ->assertSessionHasErrors('contract_end_date');
    }

    public function test_invalid_contract_type_is_rejected(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($this->makeUser('admin'))
            ->patch(route('staff.update', $staff), $this->updatePayload($staff, [
                'contract_type' => 'freelance', // not in Staff::CONTRACT_TYPES
            ]))
            ->assertSessionHasErrors('contract_type');
    }

    public function test_employment_status_defaults_to_active_on_creation(): void
    {
        // The DB column default, not app code — makeStaff() calls Staff::create()
        // directly (no StaffService involved), and an in-memory model never
        // picks up a server-side DEFAULT on its own attributes, so this must
        // re-fetch to actually observe what got persisted.
        $staff = $this->makeStaff();

        $this->assertEquals('active', $staff->fresh()->employment_status);
    }

    public function test_employment_status_can_be_updated_to_terminated(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($this->makeUser('admin'))
            ->patch(route('staff.update', $staff), $this->updatePayload($staff, [
                'employment_status' => 'terminated',
            ]))
            ->assertRedirect();

        $this->assertEquals('terminated', $staff->fresh()->employment_status);
    }

    // ── Employment details card — visible/editable only via staff.edit ──────

    public function test_employment_details_visible_to_a_user_with_staff_edit(): void
    {
        $staff = $this->makeStaff();
        $staff->update(['contract_type' => 'full_time']);

        $this->actingAs($this->makeUser('admin'))
            ->get(route('staff.show', $staff))
            ->assertSee(__('hr.employment_details'));
    }

    public function test_employment_details_hidden_from_principal_who_lacks_staff_edit(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($this->makeUser('principal'))
            ->get(route('staff.show', $staff))
            ->assertDontSee(__('hr.employment_details'));
    }

    // ── Contract document — same StaffDocument gated pattern as G1's CV upload ──

    public function test_admin_can_upload_a_contract_document(): void
    {
        $staff = $this->makeStaff();
        $file = UploadedFile::fake()->create('contract.pdf', 200, 'application/pdf');

        $this->actingAs($this->makeUser('admin'))->post(route('staff-documents.store', $staff), [
            'label' => 'Employment Contract', 'file' => $file,
        ])->assertRedirect();

        $document = StaffDocument::where('staff_id', $staff->id)->firstOrFail();
        $this->assertEquals('Employment Contract', $document->label);
    }

    public function test_permission_holder_can_download_the_contract_document(): void
    {
        $staff = $this->makeStaff();
        $document = $this->uploadContract($staff);

        $this->actingAs($this->makeUser('admin'))
            ->get(route('staff-documents.download', $document))
            ->assertOk();
    }

    public function test_the_staff_member_can_download_their_own_contract_document(): void
    {
        $staff = $this->makeStaff('librarian'); // librarian lacks staff.view per RolePermissionSeeder
        $document = $this->uploadContract($staff);

        $this->actingAs($staff->user)
            ->get(route('staff-documents.download', $document))
            ->assertOk();
    }

    public function test_unrelated_staff_member_cannot_download_someone_elses_contract_document(): void
    {
        $staff = $this->makeStaff('librarian');
        $document = $this->uploadContract($staff);
        $otherStaff = $this->makeStaff('librarian');

        $this->actingAs($otherStaff->user)
            ->get(route('staff-documents.download', $document))
            ->assertForbidden();
    }

    private function uploadContract(Staff $staff): StaffDocument
    {
        $file = UploadedFile::fake()->create('contract.pdf', 200, 'application/pdf');
        $this->actingAs($this->makeUser('admin'))->post(route('staff-documents.store', $staff), [
            'label' => 'Employment Contract', 'file' => $file,
        ]);

        return StaffDocument::where('staff_id', $staff->id)->firstOrFail();
    }
}
