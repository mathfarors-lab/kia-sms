<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('KIA School System');
    }

    public function test_admin_can_login_and_reaches_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'email'    => 'admin@kia.edu.kh',
            'password' => bcrypt('password'),
            'status'   => 'active',
        ]);
        $user->assignRole('admin');

        $response = $this->post('/login', [
            'email'    => 'admin@kia.edu.kh',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_teacher_is_redirected_to_teacher_dashboard(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('teacher');

        $this->actingAs($user)->get('/dashboard')
             ->assertRedirect(route('dashboard.teacher'));
    }

    public function test_parent_is_redirected_to_parent_dashboard(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('parent');

        $this->actingAs($user)->get('/dashboard')
             ->assertRedirect(route('dashboard.parent'));
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
