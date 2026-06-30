<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicYearTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_create_academic_year(): void
    {
        $response = $this->actingAs($this->admin)->post(route('academic-years.store'), [
            'name'       => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date'   => '2026-06-30',
            'is_active'  => '1',
        ]);

        $response->assertRedirect(route('academic-years.index'));
        $this->assertDatabaseHas('academic_years', ['name' => '2025-2026', 'is_active' => 1]);
    }

    public function test_activating_a_year_deactivates_all_others(): void
    {
        $year1 = AcademicYear::create(['name' => '2023-2024', 'start_date' => '2023-09-01', 'end_date' => '2024-06-30', 'is_active' => true]);
        $year2 = AcademicYear::create(['name' => '2024-2025', 'start_date' => '2024-09-01', 'end_date' => '2025-06-30', 'is_active' => false]);

        $this->actingAs($this->admin)->post(route('academic-years.store'), [
            'name'       => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date'   => '2026-06-30',
            'is_active'  => '1',
        ]);

        $this->assertDatabaseHas('academic_years', ['id' => $year1->id, 'is_active' => 0]);
        $this->assertDatabaseHas('academic_years', ['name' => '2025-2026', 'is_active' => 1]);
    }

    public function test_admin_can_view_classes_list(): void
    {
        $this->actingAs($this->admin)
             ->get(route('classes.index'))
             ->assertStatus(200)
             ->assertSee('Classes');
    }
}
