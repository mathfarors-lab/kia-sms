<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeTeacherWithHomeroom(Section $section): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('teacher');
        $staff = Staff::create([
            'user_id' => $user->id, 'staff_code' => 'ST-'.uniqid(),
            'position' => 'Teacher', 'department' => 'Academics',
        ]);
        $section->update(['class_teacher_id' => $staff->id]);

        return $user;
    }

    private function makeSection(string $className = 'Grade 7'): Section
    {
        $class = SchoolClass::create(['name' => $className, 'level' => $className, 'capacity' => 30]);

        return Section::create(['school_class_id' => $class->id, 'name' => 'A']);
    }

    // ── Audience scoping on create/update ───────────────────────────────────
    // ANNOUNCEMENTS_CREATE only proves a teacher may post SOMEWHERE — these
    // confirm authorizeAudience() actually restricts WHO receives it.

    public function test_teacher_can_target_their_own_section(): void
    {
        $section = $this->makeSection();
        $teacher = $this->makeTeacherWithHomeroom($section);

        $response = $this->actingAs($teacher)->post(route('announcements.store'), [
            'title' => 'Test', 'body_en' => 'Body', 'audience' => 'class', 'target_id' => $section->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('announcements', ['audience' => 'class', 'target_id' => $section->id]);
    }

    public function test_teacher_cannot_target_a_section_they_dont_teach(): void
    {
        $mySection = $this->makeSection('Grade 7');
        $otherSection = $this->makeSection('Grade 8');
        $teacher = $this->makeTeacherWithHomeroom($mySection);

        $response = $this->actingAs($teacher)->post(route('announcements.store'), [
            'title' => 'Test', 'body_en' => 'Body', 'audience' => 'class', 'target_id' => $otherSection->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('announcements', ['target_id' => $otherSection->id]);
    }

    public function test_teacher_cannot_target_a_grade_they_dont_teach(): void
    {
        $mySection = $this->makeSection('Grade 7');
        $otherSection = $this->makeSection('Grade 8');
        $teacher = $this->makeTeacherWithHomeroom($mySection);

        $response = $this->actingAs($teacher)->post(route('announcements.store'), [
            'title' => 'Test', 'body_en' => 'Body', 'audience' => 'grade', 'target_id' => $otherSection->school_class_id,
        ]);

        $response->assertForbidden();
    }

    public function test_teacher_cannot_broadcast_to_all(): void
    {
        $section = $this->makeSection();
        $teacher = $this->makeTeacherWithHomeroom($section);

        $response = $this->actingAs($teacher)->post(route('announcements.store'), [
            'title' => 'Test', 'body_en' => 'Body', 'audience' => 'all',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('announcements', ['audience' => 'all']);
    }

    public function test_teacher_cannot_update_an_announcement_to_target_a_section_they_dont_teach(): void
    {
        $mySection = $this->makeSection('Grade 7');
        $otherSection = $this->makeSection('Grade 8');
        $teacher = $this->makeTeacherWithHomeroom($mySection);

        $this->actingAs($teacher)->post(route('announcements.store'), [
            'title' => 'Test', 'body_en' => 'Body', 'audience' => 'class', 'target_id' => $mySection->id,
        ]);
        $announcement = \App\Models\Announcement::first();

        $response = $this->actingAs($teacher)->put(route('announcements.update', $announcement), [
            'title' => 'Test', 'body_en' => 'Body', 'audience' => 'class', 'target_id' => $otherSection->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('announcements', ['id' => $announcement->id, 'target_id' => $mySection->id]);
    }

    public function test_principal_can_broadcast_to_all(): void
    {
        $principal = User::factory()->create(['status' => 'active']);
        $principal->assignRole('principal');

        $response = $this->actingAs($principal)->post(route('announcements.store'), [
            'title' => 'Test', 'body_en' => 'Body', 'audience' => 'all',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('announcements', ['audience' => 'all']);
    }

    public function test_create_form_hides_all_option_from_teacher_but_not_principal(): void
    {
        $section = $this->makeSection();
        $teacher = $this->makeTeacherWithHomeroom($section);
        $principal = User::factory()->create(['status' => 'active']);
        $principal->assignRole('principal');

        $this->actingAs($teacher)->get(route('announcements.create'))
            ->assertOk()
            ->assertDontSee('<option value="all">', false);

        $this->actingAs($principal)->get(route('announcements.create'))
            ->assertOk()
            ->assertSee('<option value="all">', false);
    }
}
