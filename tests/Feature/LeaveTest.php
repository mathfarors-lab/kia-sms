<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\User;
use App\Services\LeaveService;
use App\Support\Permissions as P;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeaveTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $r    = Role::firstOrCreate(['name' => $role]);

        foreach ([P::LEAVES_SUBMIT, P::LEAVES_VIEW, P::LEAVES_MANAGE] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        if ($role === 'teacher') {
            $r->syncPermissions([P::LEAVES_SUBMIT, P::LEAVES_VIEW]);
        } elseif ($role === 'principal') {
            $r->syncPermissions([P::LEAVES_SUBMIT, P::LEAVES_VIEW, P::LEAVES_MANAGE]);
        }

        $user->assignRole($role);
        return $user;
    }

    public function test_staff_can_submit_leave(): void
    {
        $teacher = $this->makeUser('teacher');

        $this->actingAs($teacher)
            ->post(route('leaves.store'), [
                'type'       => 'sick',
                'start_date' => now()->addDay()->toDateString(),
                'end_date'   => now()->addDays(3)->toDateString(),
                'reason'     => 'Fever',
            ])
            ->assertRedirect(route('leaves.index'));

        $this->assertDatabaseHas('leaves', ['user_id' => $teacher->id, 'status' => 'pending']);
    }

    public function test_cannot_approve_own_leave(): void
    {
        $principal = $this->makeUser('principal');

        $leave = Leave::create([
            'user_id'    => $principal->id,
            'type'       => 'annual',
            'start_date' => now()->addDay()->toDateString(),
            'end_date'   => now()->addDays(2)->toDateString(),
            'status'     => 'pending',
        ]);

        $service = app(LeaveService::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $service->approve($leave, $principal);
    }

    public function test_overlap_rejection(): void
    {
        $teacher = $this->makeUser('teacher');

        Leave::create([
            'user_id'    => $teacher->id,
            'type'       => 'sick',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date'   => now()->addDays(10)->toDateString(),
            'status'     => 'pending',
        ]);

        $service = app(LeaveService::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $service->submit($teacher, [
            'type'       => 'annual',
            'start_date' => now()->addDays(7)->toDateString(),  // overlaps
            'end_date'   => now()->addDays(12)->toDateString(),
        ]);
    }

    public function test_teacher_can_only_see_own_leaves(): void
    {
        $t1 = $this->makeUser('teacher');
        $t2 = $this->makeUser('teacher');

        Leave::create(['user_id' => $t1->id, 'type' => 'sick',
            'start_date' => now()->addDay()->toDateString(),
            'end_date'   => now()->addDays(2)->toDateString(), 'status' => 'pending']);

        Leave::create(['user_id' => $t2->id, 'type' => 'sick',
            'start_date' => now()->addDay()->toDateString(),
            'end_date'   => now()->addDays(2)->toDateString(), 'status' => 'pending']);

        $this->actingAs($t1)
            ->get(route('leaves.index'))
            ->assertStatus(200)
            ->assertSee($t1->name)
            ->assertDontSee($t2->name);
    }

    public function test_principal_can_approve_others_leave(): void
    {
        $teacher   = $this->makeUser('teacher');
        $principal = $this->makeUser('principal');

        $leave = Leave::create([
            'user_id'    => $teacher->id,
            'type'       => 'annual',
            'start_date' => now()->addDay()->toDateString(),
            'end_date'   => now()->addDays(3)->toDateString(),
            'status'     => 'pending',
        ]);

        $this->actingAs($principal)
            ->post(route('leaves.approve', $leave))
            ->assertRedirect();

        $this->assertDatabaseHas('leaves', ['id' => $leave->id, 'status' => 'approved']);
    }
}
