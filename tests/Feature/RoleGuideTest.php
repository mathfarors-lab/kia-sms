<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleGuideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_can_view_the_role_guide(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->get(route('role-guide.index'))
            ->assertOk()
            ->assertSee('Role & Interface Guide');
    }

    public function test_owner_can_view_the_role_guide(): void
    {
        $this->actingAs($this->makeUser('owner'))
            ->get(route('role-guide.index'))
            ->assertOk();
    }

    public function test_non_admin_roles_cannot_view_the_role_guide(): void
    {
        foreach (['principal', 'teacher', 'accountant', 'librarian', 'receptionist', 'student', 'parent'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->get(route('role-guide.index'))
                ->assertForbidden();
        }
    }

    public function test_page_embeds_data_for_every_role(): void
    {
        $response = $this->actingAs($this->makeUser('admin'))->get(route('role-guide.index'));

        $response->assertOk();
        foreach (['owner', 'admin', 'principal', 'teacher', 'accountant', 'librarian', 'receptionist', 'student', 'parent'] as $role) {
            $response->assertSee('"' . $role . '"', false);
        }
    }
}
