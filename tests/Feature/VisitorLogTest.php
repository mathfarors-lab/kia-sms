<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Staff;
use App\Models\User;
use App\Models\VisitorLog;
use App\Support\BranchContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VisitorLogTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchA;
    private Branch $branchB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->branchA = Branch::findOrFail(1);
        $this->branchB = Branch::create(['name_en' => 'Riverside Campus', 'code' => 'RC', 'is_active' => true]);

        BranchContext::clear();
    }

    protected function tearDown(): void
    {
        BranchContext::clear();
        parent::tearDown();
    }

    private function makeReceptionist(Branch $branch): User
    {
        $user = User::factory()->create(['status' => 'active', 'branch_id' => $branch->id]);
        $user->assignRole('receptionist');
        return $user;
    }

    public function test_receptionist_can_log_a_visitor(): void
    {
        $receptionist = $this->makeReceptionist($this->branchA);

        $this->actingAs($receptionist)->post(route('visitors.store'), [
            'visitor_name' => 'John Smith',
            'purpose'      => 'Meeting with teacher',
        ])->assertRedirect(route('visitors.index'));

        $visitor = VisitorLog::where('visitor_name', 'John Smith')->firstOrFail();
        $this->assertNotNull($visitor->time_in);
        $this->assertNull($visitor->time_out);
        $this->assertEquals($receptionist->id, $visitor->recorded_by);
    }

    public function test_visitor_can_be_checked_out(): void
    {
        $receptionist = $this->makeReceptionist($this->branchA);

        $visitor = BranchContext::within($this->branchA->id, fn () => VisitorLog::create([
            'visitor_name' => 'Jane Doe', 'purpose' => 'Delivery',
            'time_in' => now(), 'recorded_by' => $receptionist->id,
        ]));

        $this->actingAs($receptionist)
            ->post(route('visitors.check-out', $visitor))
            ->assertRedirect();

        $this->assertNotNull($visitor->fresh()->time_out);
    }

    public function test_checking_out_an_already_checked_out_visitor_does_not_overwrite_the_time(): void
    {
        $receptionist = $this->makeReceptionist($this->branchA);
        $originalCheckout = Carbon::parse('2026-01-01 10:00:00');

        $visitor = BranchContext::within($this->branchA->id, fn () => VisitorLog::create([
            'visitor_name' => 'Jane Doe', 'purpose' => 'Delivery',
            'time_in' => now(), 'time_out' => $originalCheckout, 'recorded_by' => $receptionist->id,
        ]));

        $this->actingAs($receptionist)->post(route('visitors.check-out', $visitor));

        $this->assertTrue($originalCheckout->equalTo($visitor->fresh()->time_out));
    }

    public function test_index_shows_only_current_branchs_visitors(): void
    {
        $receptionistA = $this->makeReceptionist($this->branchA);

        BranchContext::within($this->branchA->id, fn () => VisitorLog::create([
            'visitor_name' => 'Branch A Visitor', 'purpose' => 'x', 'time_in' => now(), 'recorded_by' => $receptionistA->id,
        ]));
        BranchContext::within($this->branchB->id, function () {
            $receptionistB = $this->makeReceptionist($this->branchB);
            VisitorLog::create([
                'visitor_name' => 'Branch B Visitor', 'purpose' => 'x', 'time_in' => now(), 'recorded_by' => $receptionistB->id,
            ]);
        });

        $html = $this->actingAs($receptionistA)->get(route('visitors.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Branch A Visitor', $html);
        $this->assertStringNotContainsString('Branch B Visitor', $html);
    }

    public function test_purpose_is_required(): void
    {
        $receptionist = $this->makeReceptionist($this->branchA);

        $this->actingAs($receptionist)->post(route('visitors.store'), [
            'visitor_name' => 'No Purpose Given',
        ])->assertSessionHasErrors('purpose');
    }

    public function test_user_without_visitors_permission_cannot_access(): void
    {
        $teacher = User::factory()->create(['status' => 'active', 'branch_id' => $this->branchA->id]);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)->get(route('visitors.index'))->assertForbidden();
        $this->actingAs($teacher)->post(route('visitors.store'), ['visitor_name' => 'x', 'purpose' => 'x'])->assertForbidden();
    }
}
