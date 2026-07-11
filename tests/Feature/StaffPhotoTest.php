<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
    }

    private function makeAdmin(): User
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole('admin');
        return $u;
    }

    private function makeStaff(): Staff
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->assignRole('teacher');
        return Staff::create(['user_id' => $u->id, 'staff_code' => 'STF-' . uniqid(), 'position' => 'Teacher']);
    }

    public function test_create_staff_with_photo_stores_on_private_disk(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('staff.store'), [
            'name'  => 'Photo Teacher',
            'email' => 'photo.teacher@kia.test',
            'role'  => 'teacher',
            'photo' => UploadedFile::fake()->image('face.jpg'),
        ])->assertRedirect();

        $staff = Staff::whereHas('user', fn ($q) => $q->where('email', 'photo.teacher@kia.test'))->firstOrFail();

        $this->assertNotNull($staff->photo);
        Storage::disk('local')->assertExists($staff->photo);
        Storage::disk('public')->assertMissing($staff->photo);
        $this->assertStringNotContainsString('face.jpg', $staff->photo); // generated name, not user-supplied
    }

    public function test_update_replaces_photo_and_deletes_old_file(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff();

        $oldPath = UploadedFile::fake()->image('old.jpg')->store('staff/photos', 'local');
        $staff->update(['photo' => $oldPath]);

        $this->actingAs($admin)->patch(route('staff.update', $staff), [
            'name'  => $staff->user->name,
            'email' => $staff->user->email,
            'role'  => 'teacher',
            'photo' => UploadedFile::fake()->image('new.jpg'),
        ])->assertRedirect();

        $staff->refresh();
        $this->assertNotEquals($oldPath, $staff->photo);
        Storage::disk('local')->assertExists($staff->photo);
        Storage::disk('local')->assertMissing($oldPath);
    }

    public function test_update_without_photo_keeps_existing_photo(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff();

        $path = UploadedFile::fake()->image('keep.jpg')->store('staff/photos', 'local');
        $staff->update(['photo' => $path]);

        $this->actingAs($admin)->patch(route('staff.update', $staff), [
            'name'  => $staff->user->name,
            'email' => $staff->user->email,
            'role'  => 'teacher',
        ])->assertRedirect();

        $this->assertEquals($path, $staff->fresh()->photo);
        Storage::disk('local')->assertExists($path);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('staff.store'), [
            'name'  => 'Bad Upload',
            'email' => 'bad.upload@kia.test',
            'role'  => 'teacher',
            'photo' => UploadedFile::fake()->create('malware.php', 10, 'text/plain'),
        ])->assertSessionHasErrors('photo');

        $this->assertDatabaseMissing('users', ['email' => 'bad.upload@kia.test']);
    }

    // ── Gated photo route ────────────────────────────────────────────────────

    public function test_admin_and_self_can_view_staff_photo_teacher_cannot_view_others(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff();
        $path  = UploadedFile::fake()->image('p.jpg')->store('staff/photos', 'local');
        $staff->update(['photo' => $path]);

        // Admin: any staff photo.
        $this->actingAs($admin)->get(route('staff.photo', $staff))->assertOk();

        // The staff member themself.
        $this->actingAs($staff->user)->get(route('staff.photo', $staff))->assertOk();

        // A different teacher: denied.
        $other = $this->makeStaff();
        $this->actingAs($other->user)->get(route('staff.photo', $staff))->assertForbidden();
    }

    public function test_staff_photo_route_returns_placeholder_when_no_photo(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff(); // photo = null

        $this->actingAs($admin)
            ->get(route('staff.photo', $staff))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    }
}
