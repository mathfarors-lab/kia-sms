<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeAdmin(): User
    {
        $u = User::factory()->create();
        $u->assignRole('admin');
        return $u;
    }

    private function makePrincipal(): User
    {
        $u = User::factory()->create();
        $u->assignRole('principal');
        return $u;
    }

    private function makeTeacher(): User
    {
        $u = User::factory()->create();
        $u->assignRole('teacher');
        return $u;
    }

    private function makeStudent(): User
    {
        $u = User::factory()->create();
        $u->assignRole('student');
        return $u;
    }

    /** Seed a single activity row caused by the given user. */
    private function seedActivity(User $causer, string $logName = 'default', string $description = 'did something'): Activity
    {
        return activity($logName)
            ->causedBy($causer)
            ->log($description);
    }

    // ---------------------------------------------------------------
    // Access control
    // ---------------------------------------------------------------

    public function test_teacher_gets_403_on_audit_index(): void
    {
        $this->actingAs($this->makeTeacher())
             ->get(route('audit.index'))
             ->assertStatus(403);
    }

    public function test_student_gets_403_on_audit_index(): void
    {
        $this->actingAs($this->makeStudent())
             ->get(route('audit.index'))
             ->assertStatus(403);
    }

    public function test_admin_can_access_audit_index(): void
    {
        $this->actingAs($this->makeAdmin())
             ->get(route('audit.index'))
             ->assertStatus(200);
    }

    public function test_principal_can_access_audit_index(): void
    {
        $this->actingAs($this->makePrincipal())
             ->get(route('audit.index'))
             ->assertStatus(200);
    }

    // ---------------------------------------------------------------
    // Filters
    // ---------------------------------------------------------------

    public function test_causer_filter_returns_only_that_users_logs(): void
    {
        $admin  = $this->makeAdmin();
        $alice  = $this->makeTeacher();
        $bob    = $this->makeTeacher();

        $this->seedActivity($alice, 'test', 'Alice did something');
        $this->seedActivity($bob,   'test', 'Bob did something');

        $response = $this->actingAs($admin)
                         ->get(route('audit.index', ['causer_id' => $alice->id]));

        $response->assertStatus(200);
        $response->assertSee('Alice did something');
        $response->assertDontSee('Bob did something');
    }

    public function test_log_name_filter_returns_only_matching_type(): void
    {
        $admin = $this->makeAdmin();

        $this->seedActivity($admin, 'finance',    'invoice created');
        $this->seedActivity($admin, 'attendance', 'attendance marked');

        $response = $this->actingAs($admin)
                         ->get(route('audit.index', ['log_name' => 'finance']));

        $response->assertStatus(200);
        $response->assertSee('invoice created');
        $response->assertDontSee('attendance marked');
    }

    public function test_date_from_filter_excludes_older_rows(): void
    {
        $admin = $this->makeAdmin();

        // Old log — simulate by manually inserting with an old date
        \Illuminate\Support\Facades\DB::table('activity_log')->insert([
            'log_name'    => 'test',
            'description' => 'old event',
            'causer_type' => User::class,
            'causer_id'   => $admin->id,
            'properties'  => '{}',
            'event'       => null,
            'batch_uuid'  => null,
            'created_at'  => '2025-01-01 00:00:00',
            'updated_at'  => '2025-01-01 00:00:00',
        ]);

        $this->seedActivity($admin, 'test', 'recent event');

        $response = $this->actingAs($admin)
                         ->get(route('audit.index', ['date_from' => '2026-01-01']));

        $response->assertSee('recent event');
        $response->assertDontSee('old event');
    }

    public function test_date_to_filter_excludes_newer_rows(): void
    {
        $admin = $this->makeAdmin();

        \Illuminate\Support\Facades\DB::table('activity_log')->insert([
            'log_name'    => 'test',
            'description' => 'future event',
            'causer_type' => User::class,
            'causer_id'   => $admin->id,
            'properties'  => '{}',
            'event'       => null,
            'batch_uuid'  => null,
            'created_at'  => '2030-12-31 00:00:00',
            'updated_at'  => '2030-12-31 00:00:00',
        ]);

        $this->seedActivity($admin, 'test', 'current event');

        $response = $this->actingAs($admin)
                         ->get(route('audit.index', ['date_to' => '2027-01-01']));

        $response->assertSee('current event');
        $response->assertDontSee('future event');
    }

    // ---------------------------------------------------------------
    // Pagination
    // ---------------------------------------------------------------

    public function test_audit_log_is_paginated_not_full_table(): void
    {
        $admin = $this->makeAdmin();

        // Insert 30 rows with distinct timestamps so ordering is deterministic
        for ($i = 1; $i <= 30; $i++) {
            \Illuminate\Support\Facades\DB::table('activity_log')->insert([
                'log_name'    => 'test',
                'description' => "event-{$i}",
                'causer_type' => User::class,
                'causer_id'   => $admin->id,
                'properties'  => '{}',
                'event'       => null,
                'batch_uuid'  => null,
                'created_at'  => now()->subSeconds(31 - $i)->toDateTimeString(),
                'updated_at'  => now()->subSeconds(31 - $i)->toDateTimeString(),
            ]);
        }

        $response = $this->actingAs($admin)->get(route('audit.index'));

        $response->assertStatus(200);
        // 25 per page — should have a "page 2" link for the remaining 5
        $response->assertSee('page=2', false);
    }

    // ---------------------------------------------------------------
    // Staff salary must not appear in activity log
    // ---------------------------------------------------------------

    public function test_staff_activity_log_excludes_salary(): void
    {
        $admin  = $this->makeAdmin();

        $staffUser = User::factory()->create();
        $staff = \App\Models\Staff::create([
            'user_id'    => $staffUser->id,
            'staff_code' => 'S999',
            'position'   => 'Teacher',
            'department' => 'Math',
            'salary'     => 9999.99,
        ]);

        $staff->update(['position' => 'Senior Teacher', 'salary' => 12000.00]);

        // The activity log for staff must NOT contain the salary value
        $log = Activity::where('subject_type', \App\Models\Staff::class)
                       ->where('subject_id', $staff->id)
                       ->latest()
                       ->first();

        $this->assertNotNull($log, 'Expected an activity log entry for Staff update');

        $properties = $log->properties->toArray();
        $all = json_encode($properties);

        $this->assertStringNotContainsString('salary', $all);
        $this->assertStringNotContainsString('9999',   $all);
        $this->assertStringNotContainsString('12000',  $all);
    }
}
