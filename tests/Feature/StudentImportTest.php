<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    /**
     * Route::resource('students', ...) registers GET /students/{student}.
     * If /students/import is declared after it, "import" gets swallowed by
     * the {student} wildcard and route-model binding 404s before the
     * request ever reaches StudentImportController — regardless of auth.
     */
    public function test_import_form_route_is_not_shadowed_by_the_students_resource_route(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('students.import'))
            ->assertOk();
    }

    public function test_user_without_students_create_permission_cannot_view_import_form(): void
    {
        $teacher = User::factory()->create(['status' => 'active']);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)
            ->get(route('students.import'))
            ->assertForbidden();
    }
}
