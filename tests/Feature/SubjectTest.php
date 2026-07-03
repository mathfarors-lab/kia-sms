<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');
        return $admin;
    }

    // Regression test — the create/edit forms didn't collect `coefficient` even
    // though GradingService and TermGradingService both weight marks by it.
    // Every subject silently landed on the DB default (1.00) with no way to
    // change it from the UI. Same shape as the exam semester/weight gap.

    public function test_subject_store_persists_coefficient_from_the_form(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('subjects.store'), [
                'name_en'     => 'Advanced Mathematics',
                'name_km'     => 'គណិតវិទ្យាខ្ពស់',
                'code'        => 'MATH201',
                'full_mark'   => 100,
                'coefficient' => 2.5,
            ])
            ->assertRedirect(route('subjects.index'));

        $subject = Subject::where('code', 'MATH201')->firstOrFail();
        $this->assertEquals(2.5, $subject->coefficient);
    }

    public function test_subject_update_persists_coefficient_from_the_form(): void
    {
        $subject = Subject::create([
            'name_en' => 'Physics', 'name_km' => null, 'code' => 'PHY101',
            'full_mark' => 100, 'coefficient' => 1.00,
        ]);

        $this->actingAs($this->makeAdmin())
            ->put(route('subjects.update', $subject), [
                'name_en'     => 'Physics',
                'name_km'     => null,
                'code'        => 'PHY101',
                'full_mark'   => 100,
                'coefficient' => 3.0,
            ])
            ->assertRedirect(route('subjects.index'));

        $this->assertEquals(3.0, $subject->fresh()->coefficient);
    }

    // Regression test — update() was type-hinted to StoreExamRequest's sibling
    // StoreSubjectRequest, whose `code` rule is a plain `unique:subjects,code`
    // with no exception for the record being edited. Laravel runs that
    // validation automatically before the controller body's manual rule
    // override ever ran, so editing a subject WITHOUT changing its code always
    // failed with "The code has already been taken." A dedicated
    // UpdateSubjectRequest with a route-aware unique rule fixes it.

    public function test_subject_can_be_updated_without_changing_its_code(): void
    {
        $subject = Subject::create([
            'name_en' => 'Chemistry', 'name_km' => null, 'code' => 'CHEM101',
            'full_mark' => 100, 'coefficient' => 1.00,
        ]);

        $this->actingAs($this->makeAdmin())
            ->put(route('subjects.update', $subject), [
                'name_en'     => 'Chemistry (renamed)',
                'name_km'     => null,
                'code'        => 'CHEM101',
                'full_mark'   => 100,
                'coefficient' => 1.00,
            ])
            ->assertRedirect(route('subjects.index'))
            ->assertSessionDoesntHaveErrors();

        $this->assertEquals('Chemistry (renamed)', $subject->fresh()->name_en);
    }
}
