<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeworkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeTeacherWithHomeroom(Section $section): User
    {
        $teacherUser = User::factory()->create(['status' => 'active']);
        $teacherUser->assignRole('teacher');
        $staff = Staff::create([
            'user_id' => $teacherUser->id, 'staff_code' => 'ST-'.uniqid(), 'position' => 'Teacher', 'department' => 'Academics',
        ]);
        $section->update(['class_teacher_id' => $staff->id]);

        return $teacherUser;
    }

    private function makeSection(string $className): Section
    {
        $class = SchoolClass::create(['name' => $className, 'level' => $className, 'capacity' => 30]);

        return Section::create(['school_class_id' => $class->id, 'name' => 'A']);
    }

    private function payload(Section $section): array
    {
        $subject = Subject::create(['name_en' => 'Math', 'name_km' => 'M', 'code' => 'M-'.uniqid(), 'full_mark' => 100, 'coefficient' => 1]);

        return [
            'section_id'  => $section->id,
            'subject_id'  => $subject->id,
            'title'       => 'Chapter 1 exercises',
            'description' => 'Do the odd-numbered problems.',
            'due_date'    => now()->addWeek()->toDateString(),
        ];
    }

    public function test_create_form_only_lists_the_teachers_own_sections(): void
    {
        $ownSection = $this->makeSection('Grade 7');
        $otherSection = $this->makeSection('Grade 8');
        $teacher = $this->makeTeacherWithHomeroom($ownSection);

        $response = $this->actingAs($teacher)->get(route('homework.create'));

        $response->assertOk();
        $response->assertSee('Grade 7');
        $response->assertDontSee('Grade 8');
    }

    public function test_admin_create_form_lists_every_section(): void
    {
        $this->makeSection('Grade 7');
        $this->makeSection('Grade 8');
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('homework.create'));

        $response->assertOk();
        $response->assertSee('Grade 7');
        $response->assertSee('Grade 8');
    }

    public function test_teacher_can_create_homework_for_their_own_section(): void
    {
        $section = $this->makeSection('Grade 7');
        $teacher = $this->makeTeacherWithHomeroom($section);

        $response = $this->actingAs($teacher)->post(route('homework.store'), $this->payload($section));

        $response->assertRedirect();
        $this->assertDatabaseHas('homework', ['section_id' => $section->id, 'title' => 'Chapter 1 exercises']);
    }

    public function test_teacher_cannot_create_homework_for_a_section_they_dont_teach(): void
    {
        $otherSection = $this->makeSection('Grade 8');
        $teacher = $this->makeTeacherWithHomeroom($this->makeSection('Grade 7'));

        $response = $this->actingAs($teacher)->post(route('homework.store'), $this->payload($otherSection));

        $response->assertForbidden();
        $this->assertDatabaseMissing('homework', ['section_id' => $otherSection->id]);
    }

    public function test_admin_can_create_homework_for_any_section(): void
    {
        $section = $this->makeSection('Grade 9');
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');
        // Homework::teacher_id is force-set from the acting user's own staff
        // record — admin needs one too for this specific create to succeed.
        Staff::create([
            'user_id' => $admin->id, 'staff_code' => 'ST-'.uniqid(), 'position' => 'Admin', 'department' => 'Administration',
        ]);

        $response = $this->actingAs($admin)->post(route('homework.store'), $this->payload($section));

        $response->assertRedirect();
        $this->assertDatabaseHas('homework', ['section_id' => $section->id]);
    }
}
