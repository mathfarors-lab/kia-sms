<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Locks in the two Phase 1.5 product decisions:
 *  (a) TransportPolicy is permission-driven (TRANSPORT_VIEW / TRANSPORT_MANAGE),
 *      and students/parents do NOT hold TRANSPORT_VIEW (staff console only).
 *  (b) Principal has view-only homework oversight: sees everything, changes nothing.
 */
class PolicyDecisionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);
        return $user;
    }

    // ── (a) Transport access per role ────────────────────────────────────────

    public function test_admin_principal_receptionist_can_view_transport(): void
    {
        foreach (['admin', 'principal', 'receptionist'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->get(route('transport.routes.index'))
                ->assertOk();
        }
    }

    public function test_student_and_parent_cannot_view_transport_console(): void
    {
        foreach (['student', 'parent'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->get(route('transport.routes.index'))
                ->assertForbidden();
        }
    }

    public function test_principal_cannot_manage_transport_routes(): void
    {
        // Manage = TRANSPORT_MANAGE, held by admin + receptionist only.
        $this->actingAs($this->makeUser('principal'))
            ->get(route('transport.routes.create'))
            ->assertForbidden();
    }

    public function test_receptionist_can_manage_transport_routes(): void
    {
        $this->actingAs($this->makeUser('receptionist'))
            ->get(route('transport.routes.create'))
            ->assertOk();
    }

    // ── (b) Principal homework: view-only ────────────────────────────────────

    private function makeHomeworkWithSubmission(): array
    {
        $teacherUser = $this->makeUser('teacher');
        $staff = Staff::create(['user_id' => $teacherUser->id, 'staff_code' => 'STF-' . uniqid(), 'position' => 'Teacher']);

        $class   = SchoolClass::create(['name' => 'Grade ' . rand(1, 999)]);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);
        $subject = Subject::create(['name_en' => 'Math', 'name_km' => 'M', 'code' => 'M-' . uniqid(), 'full_mark' => 100]);

        $homework = Homework::create([
            'section_id' => $section->id, 'subject_id' => $subject->id, 'teacher_id' => $staff->id,
            'title' => 'Oversight HW', 'due_date' => now()->addWeek(), 'published_at' => now(),
        ]);

        $student = Student::create([
            'student_code' => 'S-' . uniqid(), 'name_en' => 'Pupil-' . uniqid(),
            'gender' => 'male', 'status' => 'enrolled',
        ]);
        $submission = HomeworkSubmission::create([
            'homework_id' => $homework->id, 'student_id' => $student->id,
            'submitted_at' => now(), 'is_late' => false,
        ]);

        return [$homework, $submission, $teacherUser];
    }

    public function test_principal_can_view_homework_index_and_show_with_submissions(): void
    {
        [$homework, , ] = $this->makeHomeworkWithSubmission();
        $principal = $this->makeUser('principal');

        $this->actingAs($principal)
            ->get(route('homework.index'))
            ->assertOk()
            ->assertSee($homework->title);

        $this->actingAs($principal)
            ->get(route('homework.show', $homework))
            ->assertOk()
            ->assertSee('Submissions');
    }

    public function test_principal_cannot_create_homework(): void
    {
        $this->makeHomeworkWithSubmission();

        $this->actingAs($this->makeUser('principal'))
            ->get(route('homework.create'))
            ->assertForbidden();
    }

    public function test_principal_cannot_grade_submission(): void
    {
        [, $submission, ] = $this->makeHomeworkWithSubmission();

        $this->actingAs($this->makeUser('principal'))
            ->post(route('homework-submissions.grade', $submission), ['grade' => 90])
            ->assertForbidden();

        $this->assertNull($submission->fresh()->grade);
    }

    public function test_principal_sees_no_grade_form_on_show_page(): void
    {
        [$homework, $submission, ] = $this->makeHomeworkWithSubmission();

        $html = $this->actingAs($this->makeUser('principal'))
            ->get(route('homework.show', $homework))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(route('homework-submissions.grade', $submission), $html);
    }

    public function test_teacher_can_still_grade_own_homework_submission(): void
    {
        [, $submission, $teacherUser] = $this->makeHomeworkWithSubmission();

        $this->actingAs($teacherUser)
            ->post(route('homework-submissions.grade', $submission), ['grade' => 85])
            ->assertRedirect();

        $this->assertEquals(85, $submission->fresh()->grade);
    }
}
