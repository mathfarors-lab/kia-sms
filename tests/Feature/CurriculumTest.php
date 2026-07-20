<?php

namespace Tests\Feature;

use App\Models\ClassSubject;
use App\Models\CurriculumTopic;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumTest extends TestCase
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

    private function makeClassSubject(?Staff $teacher = null): ClassSubject
    {
        $class = SchoolClass::create(['name' => 'Grade '.uniqid(), 'level' => 'High', 'capacity' => 30]);
        $subject = Subject::create(['name_en' => 'Math', 'name_km' => 'M', 'code' => 'M-'.uniqid(), 'full_mark' => 100, 'coefficient' => 1]);

        return ClassSubject::create([
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher?->id,
        ]);
    }

    // ── View access ──────────────────────────────────────────────────────────

    public function test_admin_can_view_the_curriculum_index_and_class_pages(): void
    {
        $classSubject = $this->makeClassSubject();

        $this->actingAs($this->makeUser('admin'))->get(route('curriculum.index'))->assertOk();
        $this->actingAs($this->makeUser('admin'))
            ->get(route('curriculum.for-class', $classSubject->school_class_id))
            ->assertOk();
        $this->actingAs($this->makeUser('admin'))
            ->get(route('curriculum.show', $classSubject))
            ->assertOk();
    }

    public function test_teacher_can_view_curriculum_via_curriculum_view_permission(): void
    {
        $classSubject = $this->makeClassSubject();

        $this->actingAs($this->makeUser('teacher'))
            ->get(route('curriculum.show', $classSubject))
            ->assertOk();
    }

    public function test_role_without_curriculum_view_is_forbidden(): void
    {
        $classSubject = $this->makeClassSubject();

        $this->actingAs($this->makeUser('librarian'))
            ->get(route('curriculum.show', $classSubject))
            ->assertForbidden();
    }

    // ── Topic CRUD (curriculum.manage only) ─────────────────────────────────

    public function test_admin_can_add_a_topic(): void
    {
        $classSubject = $this->makeClassSubject();

        $response = $this->actingAs($this->makeUser('admin'))
            ->post(route('curriculum-topics.store', $classSubject), [
                'title' => 'Fractions', 'description' => 'Intro to fractions', 'sequence' => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('curriculum_topics', [
            'class_subject_id' => $classSubject->id, 'title' => 'Fractions',
        ]);
    }

    public function test_teacher_cannot_add_a_topic(): void
    {
        $classSubject = $this->makeClassSubject();

        $this->actingAs($this->makeUser('teacher'))
            ->post(route('curriculum-topics.store', $classSubject), ['title' => 'Fractions'])
            ->assertForbidden();
    }

    public function test_admin_can_edit_a_topic(): void
    {
        $classSubject = $this->makeClassSubject();
        $topic = CurriculumTopic::create(['class_subject_id' => $classSubject->id, 'title' => 'Old Title']);

        $this->actingAs($this->makeUser('admin'))
            ->put(route('curriculum-topics.update', $topic), ['title' => 'New Title'])
            ->assertRedirect(route('curriculum.show', $classSubject));

        $this->assertEquals('New Title', $topic->fresh()->title);
    }

    public function test_admin_can_delete_a_topic(): void
    {
        $classSubject = $this->makeClassSubject();
        $topic = CurriculumTopic::create(['class_subject_id' => $classSubject->id, 'title' => 'Fractions']);

        $this->actingAs($this->makeUser('admin'))
            ->delete(route('curriculum-topics.destroy', $topic))
            ->assertRedirect();

        $this->assertDatabaseMissing('curriculum_topics', ['id' => $topic->id]);
    }

    public function test_topics_are_ordered_by_sequence(): void
    {
        $classSubject = $this->makeClassSubject();
        CurriculumTopic::create(['class_subject_id' => $classSubject->id, 'title' => 'Second', 'sequence' => 2]);
        CurriculumTopic::create(['class_subject_id' => $classSubject->id, 'title' => 'First', 'sequence' => 1]);

        $titles = $classSubject->fresh()->curriculumTopics->pluck('title')->toArray();

        $this->assertEquals(['First', 'Second'], $titles);
    }

    // ── Toggle completion — manage OR the assigned teacher (self-scoped carve-out) ──

    public function test_admin_can_toggle_topic_completion(): void
    {
        $classSubject = $this->makeClassSubject();
        $topic = CurriculumTopic::create(['class_subject_id' => $classSubject->id, 'title' => 'Fractions']);

        $this->actingAs($this->makeUser('admin'))
            ->post(route('curriculum-topics.toggle', $topic))
            ->assertRedirect();

        $topic->refresh();
        $this->assertTrue($topic->is_completed);
        $this->assertNotNull($topic->completed_at);
    }

    public function test_assigned_teacher_can_toggle_their_own_subjects_topic(): void
    {
        $teacherStaff = $this->makeStaff('teacher');
        $classSubject = $this->makeClassSubject($teacherStaff);
        $topic = CurriculumTopic::create(['class_subject_id' => $classSubject->id, 'title' => 'Fractions']);

        $this->actingAs($teacherStaff->user)
            ->post(route('curriculum-topics.toggle', $topic))
            ->assertRedirect();

        $this->assertTrue($topic->fresh()->is_completed);
    }

    public function test_unrelated_teacher_cannot_toggle_someone_elses_subject_topic(): void
    {
        $teacherStaff = $this->makeStaff('teacher');
        $classSubject = $this->makeClassSubject($teacherStaff);
        $topic = CurriculumTopic::create(['class_subject_id' => $classSubject->id, 'title' => 'Fractions']);

        $otherTeacher = $this->makeStaff('teacher');

        $this->actingAs($otherTeacher->user)
            ->post(route('curriculum-topics.toggle', $topic))
            ->assertForbidden();
    }

    public function test_toggle_twice_returns_topic_to_incomplete(): void
    {
        $classSubject = $this->makeClassSubject();
        $topic = CurriculumTopic::create(['class_subject_id' => $classSubject->id, 'title' => 'Fractions']);
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->post(route('curriculum-topics.toggle', $topic));
        $this->actingAs($admin)->post(route('curriculum-topics.toggle', $topic));

        $topic->refresh();
        $this->assertFalse($topic->is_completed);
        $this->assertNull($topic->completed_at);
    }
}
