<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\StaffDevelopmentLog;
use App\Models\StaffEvaluation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffEvaluationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

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
            'position' => 'Teacher', 'department' => 'Academics',
        ]);
    }

    private function draftPayload(): array
    {
        return [
            'evaluation_date' => now()->toDateString(),
            'overall_rating' => 4,
            'strengths' => 'Clear lesson plans.',
            'areas_for_improvement' => 'Classroom pacing.',
            'comments' => 'Solid term overall.',
        ];
    }

    // ── Create (permission boundary) ────────────────────────────────────────

    public function test_principal_can_create_an_evaluation_as_a_draft(): void
    {
        $staff = $this->makeStaff();
        $principal = $this->makeUser('principal');

        $response = $this->actingAs($principal)
            ->post(route('staff-evaluations.store', $staff), $this->draftPayload());

        $response->assertRedirect();
        $this->assertDatabaseHas('staff_evaluations', [
            'staff_id' => $staff->id,
            'evaluated_by' => $principal->id,
            'status' => StaffEvaluation::STATUS_DRAFT,
            'overall_rating' => 4,
        ]);
    }

    public function test_teacher_cannot_create_an_evaluation(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($this->makeUser('teacher'))
            ->post(route('staff-evaluations.store', $staff), $this->draftPayload())
            ->assertForbidden();
    }

    // ── Draft → finalized visibility (the core requirement) ─────────────────

    public function test_draft_evaluation_is_not_visible_to_the_evaluated_teacher(): void
    {
        $staff = $this->makeStaff();
        $evaluation = StaffEvaluation::create(array_merge($this->draftPayload(), [
            'staff_id' => $staff->id, 'status' => StaffEvaluation::STATUS_DRAFT,
        ]));

        $this->actingAs($staff->user)
            ->get(route('staff-evaluations.show', $evaluation))
            ->assertForbidden();
    }

    public function test_draft_evaluation_is_visible_to_principal(): void
    {
        $staff = $this->makeStaff();
        $evaluation = StaffEvaluation::create(array_merge($this->draftPayload(), [
            'staff_id' => $staff->id, 'status' => StaffEvaluation::STATUS_DRAFT,
        ]));

        $this->actingAs($this->makeUser('principal'))
            ->get(route('staff-evaluations.show', $evaluation))
            ->assertOk();
    }

    public function test_finalized_evaluation_becomes_visible_to_the_evaluated_teacher(): void
    {
        $staff = $this->makeStaff();
        $evaluation = StaffEvaluation::create(array_merge($this->draftPayload(), [
            'staff_id' => $staff->id, 'status' => StaffEvaluation::STATUS_FINALIZED, 'finalized_at' => now(),
        ]));

        $this->actingAs($staff->user)
            ->get(route('staff-evaluations.show', $evaluation))
            ->assertOk();
    }

    public function test_teacher_cannot_view_another_teachers_finalized_evaluation(): void
    {
        $staff = $this->makeStaff();
        $otherStaff = $this->makeStaff();
        $evaluation = StaffEvaluation::create(array_merge($this->draftPayload(), [
            'staff_id' => $staff->id, 'status' => StaffEvaluation::STATUS_FINALIZED, 'finalized_at' => now(),
        ]));

        $this->actingAs($otherStaff->user)
            ->get(route('staff-evaluations.show', $evaluation))
            ->assertForbidden();
    }

    public function test_index_lists_all_statuses_for_principal_but_only_finalized_for_the_teacher(): void
    {
        $staff = $this->makeStaff();
        StaffEvaluation::create(array_merge($this->draftPayload(), [
            'staff_id' => $staff->id, 'status' => StaffEvaluation::STATUS_DRAFT,
        ]));
        StaffEvaluation::create(array_merge($this->draftPayload(), [
            'staff_id' => $staff->id, 'status' => StaffEvaluation::STATUS_FINALIZED, 'finalized_at' => now(),
        ]));

        $principalResponse = $this->actingAs($this->makeUser('principal'))
            ->get(route('staff-evaluations.index', $staff));
        $this->assertCount(2, $principalResponse->viewData('evaluations'));

        $teacherResponse = $this->actingAs($staff->user)
            ->get(route('staff-evaluations.index', $staff));
        $teacherEvaluations = $teacherResponse->viewData('evaluations');
        $this->assertCount(1, $teacherEvaluations);
        $this->assertEquals(StaffEvaluation::STATUS_FINALIZED, $teacherEvaluations->first()->status);
    }

    // ── Finalize + one-way status guard ─────────────────────────────────────

    public function test_principal_can_finalize_a_draft_evaluation(): void
    {
        $staff = $this->makeStaff();
        $evaluation = StaffEvaluation::create(array_merge($this->draftPayload(), [
            'staff_id' => $staff->id, 'status' => StaffEvaluation::STATUS_DRAFT,
        ]));

        $this->actingAs($this->makeUser('principal'))
            ->post(route('staff-evaluations.finalize', $evaluation))
            ->assertRedirect();

        $evaluation->refresh();
        $this->assertEquals(StaffEvaluation::STATUS_FINALIZED, $evaluation->status);
        $this->assertNotNull($evaluation->finalized_at);
    }

    public function test_teacher_cannot_finalize_an_evaluation(): void
    {
        $staff = $this->makeStaff();
        $evaluation = StaffEvaluation::create(array_merge($this->draftPayload(), [
            'staff_id' => $staff->id, 'status' => StaffEvaluation::STATUS_DRAFT,
        ]));

        $this->actingAs($staff->user)
            ->post(route('staff-evaluations.finalize', $evaluation))
            ->assertForbidden();
    }

    public function test_finalized_evaluation_cannot_be_edited(): void
    {
        $staff = $this->makeStaff();
        $evaluation = StaffEvaluation::create(array_merge($this->draftPayload(), [
            'staff_id' => $staff->id, 'status' => StaffEvaluation::STATUS_FINALIZED, 'finalized_at' => now(),
        ]));

        $this->actingAs($this->makeUser('principal'))
            ->get(route('staff-evaluations.edit', $evaluation))
            ->assertForbidden();

        $this->actingAs($this->makeUser('principal'))
            ->put(route('staff-evaluations.update', $evaluation), $this->draftPayload())
            ->assertForbidden();
    }

    public function test_draft_evaluation_can_be_edited_by_principal(): void
    {
        $staff = $this->makeStaff();
        $evaluation = StaffEvaluation::create(array_merge($this->draftPayload(), [
            'staff_id' => $staff->id, 'status' => StaffEvaluation::STATUS_DRAFT,
        ]));

        $this->actingAs($this->makeUser('principal'))
            ->put(route('staff-evaluations.update', $evaluation), array_merge($this->draftPayload(), [
                'overall_rating' => 2,
            ]))
            ->assertRedirect();

        $this->assertEquals(2, $evaluation->fresh()->overall_rating);
    }

    // ── Development / CPD log — gated staff.edit, deliberately NOT the new permission ──

    public function test_admin_can_add_a_development_log(): void
    {
        $staff = $this->makeStaff();

        $response = $this->actingAs($this->makeUser('admin'))->post(route('staff-development-logs.store', $staff), [
            'title' => 'Inclusive Classrooms Workshop',
            'provider' => 'MoEYS',
            'completed_date' => now()->toDateString(),
            'hours' => 6,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('staff_development_logs', [
            'staff_id' => $staff->id, 'title' => 'Inclusive Classrooms Workshop', 'provider' => 'MoEYS',
        ]);
    }

    public function test_admin_can_delete_a_development_log(): void
    {
        $staff = $this->makeStaff();
        $log = StaffDevelopmentLog::create([
            'staff_id' => $staff->id, 'title' => 'Workshop', 'completed_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->makeUser('admin'))
            ->delete(route('staff-development-logs.destroy', $log))
            ->assertRedirect();

        $this->assertDatabaseMissing('staff_development_logs', ['id' => $log->id]);
    }

    public function test_teacher_cannot_add_a_development_log(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($this->makeUser('teacher'))->post(route('staff-development-logs.store', $staff), [
            'title' => 'Workshop', 'completed_date' => now()->toDateString(),
        ])->assertForbidden();
    }

    public function test_principal_cannot_add_a_development_log_since_it_requires_staff_edit_not_evaluations_manage(): void
    {
        // Deliberate: staff-evaluations.manage and staff.edit are separate
        // permissions (principal has the former, not the latter). This locks
        // in that CPD-log editing stays on the existing staff.edit gate.
        $staff = $this->makeStaff();

        $this->actingAs($this->makeUser('principal'))->post(route('staff-development-logs.store', $staff), [
            'title' => 'Workshop', 'completed_date' => now()->toDateString(),
        ])->assertForbidden();
    }

    public function test_development_logs_are_ordered_by_completed_date_descending(): void
    {
        $staff = $this->makeStaff();
        StaffDevelopmentLog::create(['staff_id' => $staff->id, 'title' => 'Old', 'completed_date' => '2020-01-01']);
        StaffDevelopmentLog::create(['staff_id' => $staff->id, 'title' => 'New', 'completed_date' => '2025-01-01']);

        $titles = $staff->fresh()->developmentLogs->pluck('title')->toArray();

        $this->assertEquals(['New', 'Old'], $titles);
    }
}
