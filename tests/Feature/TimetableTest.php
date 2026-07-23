<?php

namespace Tests\Feature;

use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\Timetable;
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

    private function makeSubject(): Subject
    {
        return Subject::create([
            'name_en' => 'Subject-' . uniqid(), 'name_km' => null,
            'code' => 'SUB-' . uniqid(), 'coefficient' => 1.0, 'full_mark' => 100,
        ]);
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');
        return $admin;
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

    /**
     * A teacher who teaches a SUBJECT in a class but isn't its homeroom
     * teacher must still be able to view that section's own timetable —
     * previously blocked because the check only looked at class_teacher_id.
     */
    public function test_subject_taught_teacher_can_view_a_section_they_dont_hold_homeroom_for(): void
    {
        [$user, $staff] = $this->makeTeacher();
        $section = $this->makeSection(); // no homeroom teacher assigned
        $subject = $this->makeSubject();
        ClassSubject::create([
            'school_class_id' => $section->school_class_id,
            'subject_id'      => $subject->id,
            'teacher_id'      => $staff->id,
        ]);

        $this->actingAs($user)
            ->get(route('timetable.show', $section))
            ->assertOk();
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

    // ── Standalone picker page ("Timetable" sidebar link) ────────────────────────

    public function test_admin_can_view_the_timetable_picker_listing_every_section(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $sectionA = $this->makeSection();
        $sectionB = $this->makeSection();

        $html = $this->actingAs($admin)
            ->get(route('timetables.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('timetable.show', $sectionA), $html);
        $this->assertStringContainsString(route('timetable.show', $sectionB), $html);
    }

    public function test_teacher_cannot_view_the_timetable_picker(): void
    {
        [$user] = $this->makeTeacher();

        $this->actingAs($user)
            ->get(route('timetables.index'))
            ->assertForbidden();
    }

    public function test_principal_cannot_view_the_timetable_picker(): void
    {
        // Principal holds sections.manage (reaches the Classes & Sections
        // drill-down) but not timetables.manage specifically — the picker
        // stays gated to the narrower permission, same as the picker link.
        $principal = User::factory()->create(['status' => 'active']);
        $principal->assignRole('principal');

        $this->actingAs($principal)
            ->get(route('timetables.index'))
            ->assertForbidden();
    }

    // ── Teacher-centric schedule view ────────────────────────────────────────────

    public function test_teacher_can_view_their_own_teaching_schedule(): void
    {
        [$user, $staff] = $this->makeTeacher();

        $this->actingAs($user)
            ->get(route('staff.teaching-schedule', $staff))
            ->assertOk();
    }

    public function test_teacher_cannot_view_another_teachers_schedule(): void
    {
        [$userA] = $this->makeTeacher();
        [, $staffB] = $this->makeTeacher();

        $this->actingAs($userA)
            ->get(route('staff.teaching-schedule', $staffB))
            ->assertForbidden();
    }

    public function test_admin_can_view_any_teachers_schedule(): void
    {
        [, $staff] = $this->makeTeacher();

        $this->actingAs($this->makeAdmin())
            ->get(route('staff.teaching-schedule', $staff))
            ->assertOk();
    }

    public function test_teaching_schedule_shows_the_correct_slots_for_the_correct_teacher_only(): void
    {
        [, $teacherA] = $this->makeTeacher();
        [, $teacherB] = $this->makeTeacher();
        $section = $this->makeSection();
        $subject = $this->makeSubject();

        Timetable::create([
            'section_id' => $section->id, 'subject_id' => $subject->id, 'teacher_id' => $teacherA->id,
            'day' => 'monday', 'period' => 1, 'start_time' => '07:00', 'end_time' => '08:00',
        ]);
        Timetable::create([
            'section_id' => $section->id, 'subject_id' => $subject->id, 'teacher_id' => $teacherB->id,
            'day' => 'tuesday', 'period' => 2, 'start_time' => '08:00', 'end_time' => '09:00',
        ]);

        $html = $this->actingAs($this->makeAdmin())
            ->get(route('staff.teaching-schedule', $teacherA))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($subject->name_en, $html);
        // teacherA's own slot appears; teacherB's slot (different day/period) does not
        // leak onto teacherA's page.
        $this->assertStringNotContainsString('data-id="' . Timetable::where('teacher_id', $teacherB->id)->value('id') . '"', $html);
    }

    public function test_non_teacher_staff_profile_does_not_show_the_teaching_schedule_link(): void
    {
        $admin = $this->makeAdmin();
        $accountantUser = User::factory()->create(['status' => 'active']);
        $accountantUser->assignRole('accountant');
        $accountantStaff = Staff::create(['user_id' => $accountantUser->id, 'staff_code' => 'STF-' . uniqid(), 'position' => 'Accountant']);

        $html = $this->actingAs($admin)
            ->get(route('staff.show', $accountantStaff))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(route('staff.teaching-schedule', $accountantStaff), $html);
    }

    public function test_teacher_staff_profile_shows_the_teaching_schedule_link(): void
    {
        [, $staff] = $this->makeTeacher();

        $html = $this->actingAs($this->makeAdmin())
            ->get(route('staff.show', $staff))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('staff.teaching-schedule', $staff), $html);
    }

    public function test_teacher_cannot_add_a_slot_from_their_own_schedule_view(): void
    {
        [$user, $staff] = $this->makeTeacher();
        $section = $this->makeSection();
        $subject = $this->makeSubject();

        // The teaching-schedule page reuses timetable.store — same
        // authorization regardless of which UI initiated the request.
        $this->actingAs($user)
            ->post(route('timetable.store', $section), [
                'subject_id' => $subject->id, 'teacher_id' => $staff->id,
                'day' => 'monday', 'period' => 1, 'start_time' => '07:00', 'end_time' => '08:00',
            ])
            ->assertForbidden();
    }

    // ── Clash detection is shared regardless of which side initiated the slot ───

    public function test_slot_added_from_section_side_blocks_a_teacher_side_clash(): void
    {
        [, $teacher] = $this->makeTeacher();
        $sectionX = $this->makeSection();
        $sectionY = $this->makeSection();
        $subject  = $this->makeSubject();
        $admin    = $this->makeAdmin();

        // Created the way the SECTION-side UI would: section fixed, teacher picked.
        $this->actingAs($admin)->post(route('timetable.store', $sectionX), [
            'subject_id' => $subject->id, 'teacher_id' => $teacher->id,
            'day' => 'monday', 'period' => 3, 'start_time' => '09:00', 'end_time' => '10:00',
        ])->assertOk();

        // Attempted the way the TEACHER-side UI would: different section,
        // same teacher, same day/period — must still be rejected.
        $response = $this->actingAs($admin)->post(route('timetable.store', $sectionY), [
            'subject_id' => $subject->id, 'teacher_id' => $teacher->id,
            'day' => 'monday', 'period' => 3, 'start_time' => '09:00', 'end_time' => '10:00',
        ]);

        $response->assertStatus(422);
        $this->assertEquals(1, Timetable::where('teacher_id', $teacher->id)->count());
    }

    public function test_slot_added_from_teacher_side_blocks_a_section_side_clash(): void
    {
        [, $teacherA] = $this->makeTeacher();
        [, $teacherB] = $this->makeTeacher();
        $section = $this->makeSection();
        $subject = $this->makeSubject();
        $admin   = $this->makeAdmin();

        // Created the way the TEACHER-side UI would (teacherA fixed, section picked).
        $this->actingAs($admin)->post(route('timetable.store', $section), [
            'subject_id' => $subject->id, 'teacher_id' => $teacherA->id,
            'day' => 'wednesday', 'period' => 5, 'start_time' => '11:00', 'end_time' => '12:00',
        ])->assertOk();

        // Attempted the way the SECTION-side UI would: same section, same
        // day/period, a different teacher — the SECTION is already taken
        // regardless of who's teaching, so this must also be rejected.
        $response = $this->actingAs($admin)->post(route('timetable.store', $section), [
            'subject_id' => $subject->id, 'teacher_id' => $teacherB->id,
            'day' => 'wednesday', 'period' => 5, 'start_time' => '11:00', 'end_time' => '12:00',
        ]);

        $response->assertStatus(422);
        $this->assertEquals(1, Timetable::where('section_id', $section->id)->count());
    }
}
