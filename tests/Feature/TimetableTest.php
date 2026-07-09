<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeTeacher(): array
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('teacher');
        $staff = Staff::create([
            'user_id'    => $user->id,
            'staff_code' => 'STF-' . uniqid(),
            'position'   => 'Teacher',
        ]);
        return [$user, $staff];
    }

    private function makeSection(?Staff $classTeacher = null): Section
    {
        $class = SchoolClass::create(['name' => 'Grade ' . rand(1, 99), 'level' => 'High', 'capacity' => 30]);
        return Section::create([
            'school_class_id'  => $class->id,
            'name'             => 'A',
            'class_teacher_id' => $classTeacher?->id,
        ]);
    }

    public function test_teacher_can_view_own_section_timetable_read_only(): void
    {
        [$user, $staff] = $this->makeTeacher();
        $section = $this->makeSection($staff);

        $html = $this->actingAs($user)
            ->get(route('timetable.show', $section))
            ->assertOk()
            ->getContent();

        // Read-only: no add-slot trigger, no interactive form/modal rendered.
        // (Not checking for the store URL string — GET timetable.show and
        // POST timetable.store share the same URI, so it's always present
        // as the current page's own address regardless of $canManage.)
        $this->assertStringNotContainsString('openModal(', $html);
        $this->assertStringNotContainsString('id="slot-modal"', $html);
        $this->assertStringNotContainsString('id="slot-form"', $html);
    }

    public function test_teacher_cannot_view_another_sections_timetable(): void
    {
        [$user] = $this->makeTeacher();
        [, $otherTeacherStaff] = $this->makeTeacher();
        $otherSection = $this->makeSection($otherTeacherStaff);

        $this->actingAs($user)
            ->get(route('timetable.show', $otherSection))
            ->assertForbidden();
    }

    public function test_teacher_cannot_add_slot_even_on_own_section(): void
    {
        [$user, $staff] = $this->makeTeacher();
        $section = $this->makeSection($staff);

        $this->actingAs($user)
            ->post(route('timetable.store', $section), [
                'subject_id' => 1,
                'day'        => 'monday',
                'period'     => 1,
                'start_time' => '07:00',
                'end_time'   => '08:00',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_still_manage_any_section_timetable(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');
        $section = $this->makeSection();

        $html = $this->actingAs($admin)
            ->get(route('timetable.show', $section))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('openModal(', $html);
    }

    public function test_sidebar_shows_my_timetable_only_for_class_teacher(): void
    {
        [$homeroomTeacher, $homeroomStaff] = $this->makeTeacher();
        $section = $this->makeSection($homeroomStaff);

        $this->actingAs($homeroomTeacher)
            ->followingRedirects()
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('timetable.show', $section), false);

        [$plainTeacher] = $this->makeTeacher();

        // Not scoped to any section as class teacher — no timetable.show
        // link of any kind should appear (checked via URL, not the "My
        // Timetable" label text, which the teacher dashboard body also
        // happens to use for an unrelated attendance-marking card).
        $html = $this->actingAs($plainTeacher)
            ->followingRedirects()
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('/timetable"', $html);
    }
}
