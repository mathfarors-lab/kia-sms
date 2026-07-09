<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeParent(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('parent');
        return $user;
    }

    private function makeStudent(string $status): Student
    {
        return Student::create([
            'name_en' => 'Child-' . uniqid(), 'student_code' => 'S-' . uniqid(),
            'gender' => 'male', 'status' => $status,
        ]);
    }

    private function linkChild(User $parent, Student $child): void
    {
        $parent->wards()->attach($child->id, ['relation' => 'parent', 'is_primary' => true]);
    }

    public function test_enrolled_child_gets_green_pill(): void
    {
        $parent  = $this->makeParent();
        $student = $this->makeStudent('enrolled');
        $this->linkChild($parent, $student);

        $html = $this->actingAs($parent)->get(route('dashboard.parent'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/pill pill-ok"[^>]*>\s*enrolled/s',
            $html
        );
    }

    public function test_dropped_child_gets_muted_pill_not_ok_or_bad(): void
    {
        $parent  = $this->makeParent();
        $student = $this->makeStudent('dropped');
        $this->linkChild($parent, $student);

        $html = $this->actingAs($parent)->get(route('dashboard.parent'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/pill pill-muted"[^>]*>\s*dropped/s', $html);
        $this->assertDoesNotMatchRegularExpression('/pill pill-bad"[^>]*>\s*dropped/s', $html);
    }

    public function test_transferred_and_graduated_children_get_distinct_non_alarming_pills(): void
    {
        $parent = $this->makeParent();

        $transferred = $this->makeStudent('transferred');
        $graduated   = $this->makeStudent('graduated');
        $this->linkChild($parent, $transferred);
        $this->linkChild($parent, $graduated);

        $html = $this->actingAs($parent)->get(route('dashboard.parent'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/pill pill-royal"[^>]*>\s*transferred/s', $html);
        $this->assertMatchesRegularExpression('/pill pill-gold"[^>]*>\s*graduated/s', $html);
        // Neither lifecycle state should ever render as the alarming "bad" (red) pill.
        $this->assertDoesNotMatchRegularExpression('/pill pill-bad"[^>]*>\s*(transferred|graduated)/s', $html);
    }

    public function test_enrolled_and_dropped_children_together_get_different_pill_classes(): void
    {
        $parent  = $this->makeParent();
        $active  = $this->makeStudent('enrolled');
        $dropped = $this->makeStudent('dropped');
        $this->linkChild($parent, $active);
        $this->linkChild($parent, $dropped);

        $html = $this->actingAs($parent)->get(route('dashboard.parent'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/pill pill-ok"[^>]*>\s*enrolled/s', $html);
        $this->assertMatchesRegularExpression('/pill pill-muted"[^>]*>\s*dropped/s', $html);
    }
}
