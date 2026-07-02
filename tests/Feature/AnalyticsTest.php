<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\User;
use App\Support\Permissions as P;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Permission::firstOrCreate(['name' => P::ANALYTICS_VIEW]);
        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->givePermissionTo(P::ANALYTICS_VIEW);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('admin');
        return $user;
    }

    private function year(): AcademicYear
    {
        return AcademicYear::create([
            'name'       => 'Test Year',
            'start_date' => '2026-01-01',
            'end_date'   => '2026-12-31',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_analytics(): void
    {
        $this->year();

        $this->actingAs($this->admin())
            ->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('Analytics');
    }

    public function test_analytics_page_shows_year_filter(): void
    {
        $year = $this->year();

        $this->actingAs($this->admin())
            ->get(route('analytics.index', ['year_id' => $year->id]))
            ->assertOk()
            ->assertSee($year->name);
    }

    public function test_unauthenticated_cannot_view_analytics(): void
    {
        $this->year();

        $this->get(route('analytics.index'))
            ->assertRedirect(route('login'));
    }
}
