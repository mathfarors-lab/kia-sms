<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardGuardTest extends TestCase
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

    // ── Wrong-role access redirects, never renders ──────────────────────────────

    public function test_teacher_hitting_accountant_dashboard_is_redirected_to_their_own(): void
    {
        $teacher = $this->makeUser('teacher');

        $this->actingAs($teacher)
            ->get(route('dashboard.accountant'))
            ->assertRedirect(route('dashboard.teacher'));
    }

    public function test_accountant_hitting_admin_dashboard_is_redirected_to_their_own(): void
    {
        $accountant = $this->makeUser('accountant');

        $this->actingAs($accountant)
            ->get(route('dashboard.admin'))
            ->assertRedirect(route('dashboard.accountant'));
    }

    public function test_admin_hitting_principal_dashboard_is_redirected_to_their_own(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->get(route('dashboard.principal'))
            ->assertRedirect(route('dashboard.admin'));
    }

    public function test_principal_hitting_teacher_dashboard_is_redirected_to_their_own(): void
    {
        $principal = $this->makeUser('principal');

        $this->actingAs($principal)
            ->get(route('dashboard.teacher'))
            ->assertRedirect(route('dashboard.principal'));
    }

    public function test_librarian_hitting_receptionist_dashboard_is_redirected_to_their_own(): void
    {
        $librarian = $this->makeUser('librarian');

        $this->actingAs($librarian)
            ->get(route('dashboard.receptionist'))
            ->assertRedirect(route('dashboard.librarian'));
    }

    public function test_receptionist_hitting_librarian_dashboard_is_redirected_to_their_own(): void
    {
        $receptionist = $this->makeUser('receptionist');

        $this->actingAs($receptionist)
            ->get(route('dashboard.librarian'))
            ->assertRedirect(route('dashboard.receptionist'));
    }

    public function test_student_hitting_parent_dashboard_is_redirected_to_their_own(): void
    {
        $student = $this->makeUser('student');

        $this->actingAs($student)
            ->get(route('dashboard.parent'))
            ->assertRedirect(route('dashboard.student'));
    }

    public function test_parent_hitting_student_dashboard_is_redirected_to_their_own(): void
    {
        $parent = $this->makeUser('parent');

        $this->actingAs($parent)
            ->get(route('dashboard.student'))
            ->assertRedirect(route('dashboard.parent'));
    }

    // ── Regression safety: legitimate own-dashboard access must still work ──────

    public function test_every_role_can_still_reach_their_own_dashboard(): void
    {
        $roleRoutes = [
            'admin'        => 'dashboard.admin',
            'principal'    => 'dashboard.principal',
            'teacher'      => 'dashboard.teacher',
            'accountant'   => 'dashboard.accountant',
            'librarian'    => 'dashboard.librarian',
            'receptionist' => 'dashboard.receptionist',
            'student'      => 'dashboard.student',
            'parent'       => 'dashboard.parent',
        ];

        foreach ($roleRoutes as $role => $routeName) {
            $user = $this->makeUser($role);
            $this->actingAs($user)->get(route($routeName))->assertOk();
        }
    }

    public function test_generic_dashboard_route_still_redirects_to_the_correct_role_dashboard(): void
    {
        $teacher = $this->makeUser('teacher');

        $this->actingAs($teacher)
            ->get(route('dashboard'))
            ->assertRedirect(route('dashboard.teacher'));
    }

    /** Owner's dashboard was already correctly guarded (role:owner) before this phase — untouched, still 403s. */
    public function test_owner_dashboard_still_forbidden_to_non_owners(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->get(route('owner.dashboard'))
            ->assertForbidden();
    }
}
