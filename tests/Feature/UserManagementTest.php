<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Permissions as P;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('admin');
        return $user;
    }

    private function teacher(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('teacher');
        return $user;
    }

    // ── Access control ────────────────────────────────────────────────────────

    public function test_non_admin_cannot_access_users_index(): void
    {
        $this->actingAs($this->teacher())
             ->get(route('users.index'))
             ->assertForbidden();
    }

    public function test_admin_can_list_users(): void
    {
        $admin = $this->admin();
        User::factory(3)->create();

        $this->actingAs($admin)
             ->get(route('users.index'))
             ->assertOk()
             ->assertSee($admin->name);
    }

    // ── Create user ────────────────────────────────────────────────────────────

    public function test_admin_can_create_user_with_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
             ->post(route('users.store'), [
                 'name'                  => 'Test Teacher',
                 'email'                 => 'test.teacher@school.test',
                 'password'              => 'Secret@1234',
                 'password_confirmation' => 'Secret@1234',
                 'roles'                 => ['teacher'],
                 'status'                => 'active',
             ])
             ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['email' => 'test.teacher@school.test']);
        $created = User::where('email', 'test.teacher@school.test')->first();
        $this->assertTrue($created->hasRole('teacher'));
    }

    // ── Role assignment persists ────────────────────────────────────────────────

    public function test_role_assignment_persists_after_update(): void
    {
        $admin  = $this->admin();
        $target = $this->teacher();

        $this->actingAs($admin)
             ->patch(route('users.update', $target), [
                 'name'   => $target->name,
                 'email'  => $target->email,
                 'roles'  => ['teacher', 'librarian'],
                 'status' => 'active',
             ])
             ->assertRedirect(route('users.index'));

        $target->refresh();
        $this->assertTrue($target->hasRole('librarian'));
        $this->assertTrue($target->hasRole('teacher'));
    }

    // ── Safety: cannot self-deactivate ────────────────────────────────────────

    public function test_admin_cannot_self_deactivate_via_update(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
             ->patch(route('users.update', $admin), [
                 'name'   => $admin->name,
                 'email'  => $admin->email,
                 'roles'  => ['admin'],
                 'status' => 'inactive',
             ])
             ->assertSessionHasErrors('status');

        $this->assertEquals('active', $admin->fresh()->status);
    }

    public function test_admin_cannot_self_deactivate_via_toggle(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
             ->post(route('users.toggle-status', $admin))
             ->assertForbidden();

        $this->assertEquals('active', $admin->fresh()->status);
    }

    // ── Safety: cannot remove last admin ──────────────────────────────────────

    public function test_cannot_strip_admin_role_when_only_one_active_admin(): void
    {
        $admin = $this->admin();

        // admin is the ONLY active admin
        $this->actingAs($admin)
             ->patch(route('users.update', $admin), [
                 'name'   => $admin->name,
                 'email'  => $admin->email,
                 'roles'  => ['teacher'], // removing admin role
                 'status' => 'active',
             ])
             ->assertSessionHasErrors('roles');

        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }

    public function test_cannot_deactivate_last_active_admin_via_toggle(): void
    {
        $admin = $this->admin();
        $other = $this->admin(); // second admin so toggle-status on $other can be tested

        // Deactivate second admin - should work since first still active
        $this->actingAs($admin)
             ->post(route('users.toggle-status', $other))
             ->assertRedirect();

        $this->assertEquals('inactive', $other->fresh()->status);

        // Now $admin is the last active admin — toggling admin should 403
        $this->actingAs($admin)
             ->post(route('users.toggle-status', $admin))
             ->assertForbidden();
    }

    // ── Safety: deactivated user cannot login ─────────────────────────────────

    public function test_deactivated_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email'    => 'inactive@school.test',
            'password' => Hash::make('Secret@1234'),
            'status'   => 'inactive',
        ]);
        $user->assignRole('teacher');

        $this->post(route('login'), [
            'email'    => 'inactive@school.test',
            'password' => 'Secret@1234',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
