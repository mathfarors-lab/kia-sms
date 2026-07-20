<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\DisciplineIncident;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\DisciplineIncidentLogged;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DisciplineIncidentTest extends TestCase
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

    private function makeStudent(): Student
    {
        return Student::create([
            'student_code' => 'S-'.uniqid(), 'name_en' => 'Student-'.uniqid(),
            'gender' => 'male', 'status' => 'enrolled',
        ]);
    }

    private function activeYear(): AcademicYear
    {
        return AcademicYear::create([
            'name' => '2026-2027', 'start_date' => '2026-08-01', 'end_date' => '2027-05-31', 'is_active' => true,
        ]);
    }

    private function makeSection(): Section
    {
        $class = SchoolClass::create(['name' => 'Grade '.uniqid(), 'level' => 'High', 'capacity' => 30]);

        return Section::create(['school_class_id' => $class->id, 'name' => 'A']);
    }

    private function payload(): array
    {
        return [
            'incident_date' => now()->toDateString(),
            'type' => 'disruptive_behavior',
            'description' => 'Talked back to the teacher during class.',
            'action_taken' => 'Verbal warning issued.',
        ];
    }

    // ── Create — permission + scoping ───────────────────────────────────────

    public function test_principal_can_log_an_incident_for_any_student(): void
    {
        $student = $this->makeStudent();

        $response = $this->actingAs($this->makeUser('principal'))
            ->post(route('discipline-incidents.store', $student), $this->payload());

        $response->assertRedirect();
        $this->assertDatabaseHas('discipline_incidents', [
            'student_id' => $student->id, 'type' => 'disruptive_behavior',
        ]);
    }

    public function test_homeroom_teacher_can_log_an_incident_for_their_own_student(): void
    {
        $year = $this->activeYear();
        $teacherStaff = $this->makeStaff('teacher');
        $section = $this->makeSection();
        $section->update(['class_teacher_id' => $teacherStaff->id]);
        $student = $this->makeStudent();
        $student->sections()->attach($section->id, ['academic_year_id' => $year->id]);

        $this->actingAs($teacherStaff->user)
            ->post(route('discipline-incidents.store', $student), $this->payload())
            ->assertRedirect();

        $this->assertDatabaseHas('discipline_incidents', ['student_id' => $student->id]);
    }

    public function test_subject_teacher_can_log_an_incident_for_their_taught_students(): void
    {
        $year = $this->activeYear();
        $teacherStaff = $this->makeStaff('teacher');
        $section = $this->makeSection();
        $subject = Subject::create(['name_en' => 'Math', 'name_km' => 'M', 'code' => 'M-'.uniqid(), 'full_mark' => 100, 'coefficient' => 1]);
        ClassSubject::create([
            'school_class_id' => $section->school_class_id, 'subject_id' => $subject->id, 'teacher_id' => $teacherStaff->id,
        ]);
        $student = $this->makeStudent();
        $student->sections()->attach($section->id, ['academic_year_id' => $year->id]);

        $this->actingAs($teacherStaff->user)
            ->post(route('discipline-incidents.store', $student), $this->payload())
            ->assertRedirect();

        $this->assertDatabaseHas('discipline_incidents', ['student_id' => $student->id]);
    }

    public function test_unrelated_teacher_cannot_log_an_incident(): void
    {
        $this->activeYear();
        $teacherStaff = $this->makeStaff('teacher');
        $student = $this->makeStudent(); // not enrolled in any of the teacher's sections

        $this->actingAs($teacherStaff->user)
            ->post(route('discipline-incidents.store', $student), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseMissing('discipline_incidents', ['student_id' => $student->id]);
    }

    public function test_role_without_discipline_manage_is_forbidden(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($this->makeUser('accountant'))
            ->post(route('discipline-incidents.store', $student), $this->payload())
            ->assertForbidden();
    }

    public function test_invalid_type_is_rejected(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($this->makeUser('principal'))
            ->post(route('discipline-incidents.store', $student), array_merge($this->payload(), ['type' => 'not_a_real_type']))
            ->assertSessionHasErrors('type');
    }

    // ── Guardian notification ───────────────────────────────────────────────

    public function test_creating_an_incident_notifies_the_primary_guardian(): void
    {
        Notification::fake();

        $student = $this->makeStudent();
        $primary = User::factory()->create();
        $secondary = User::factory()->create();
        $student->guardians()->attach($primary->id, ['relation' => 'mother', 'is_primary' => true]);
        $student->guardians()->attach($secondary->id, ['relation' => 'father', 'is_primary' => false]);

        $this->actingAs($this->makeUser('principal'))
            ->post(route('discipline-incidents.store', $student), $this->payload());

        Notification::assertSentTo($primary, DisciplineIncidentLogged::class);
        Notification::assertNotSentTo($secondary, DisciplineIncidentLogged::class);
    }

    public function test_creating_an_incident_notifies_the_only_guardian_when_none_is_primary(): void
    {
        Notification::fake();

        $student = $this->makeStudent();
        $guardian = User::factory()->create();
        $student->guardians()->attach($guardian->id, ['relation' => 'father', 'is_primary' => false]);

        $this->actingAs($this->makeUser('principal'))
            ->post(route('discipline-incidents.store', $student), $this->payload());

        Notification::assertSentTo($guardian, DisciplineIncidentLogged::class);
    }

    public function test_no_notification_sent_and_no_error_when_student_has_no_guardian(): void
    {
        Notification::fake();

        $student = $this->makeStudent();

        $response = $this->actingAs($this->makeUser('principal'))
            ->post(route('discipline-incidents.store', $student), $this->payload());

        $response->assertRedirect();
        Notification::assertNothingSent();
    }

    public function test_updating_an_incident_does_not_resend_the_notification(): void
    {
        $student = $this->makeStudent();
        $guardian = User::factory()->create();
        $student->guardians()->attach($guardian->id, ['relation' => 'mother', 'is_primary' => true]);

        $this->actingAs($this->makeUser('principal'))
            ->post(route('discipline-incidents.store', $student), $this->payload());
        $incident = DisciplineIncident::where('student_id', $student->id)->firstOrFail();

        Notification::fake();
        $this->actingAs($this->makeUser('principal'))
            ->put(route('discipline-incidents.update', $incident), array_merge($this->payload(), [
                'description' => 'Updated description.',
            ]));

        Notification::assertNothingSent();
        $this->assertEquals('Updated description.', $incident->fresh()->description);
    }

    // ── Parent visibility — ownership check, not a permission ──────────────

    public function test_parent_can_view_their_own_childs_discipline_records(): void
    {
        $student = $this->makeStudent();
        $parent = $this->makeUser('parent');
        $student->guardians()->attach($parent->id, ['relation' => 'mother', 'is_primary' => true]);

        $this->actingAs($parent)
            ->get(route('discipline-incidents.index', $student))
            ->assertOk();
    }

    public function test_parent_cannot_view_another_childs_discipline_records(): void
    {
        $student = $this->makeStudent();
        $parent = $this->makeUser('parent'); // no guardian link to this student

        $this->actingAs($parent)
            ->get(route('discipline-incidents.index', $student))
            ->assertForbidden();
    }

    public function test_student_role_cannot_view_discipline_records(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($this->makeUser('student'))
            ->get(route('discipline-incidents.index', $student))
            ->assertForbidden();
    }

    // ── Ordering ─────────────────────────────────────────────────────────────

    public function test_incidents_are_ordered_newest_first(): void
    {
        $student = $this->makeStudent();
        DisciplineIncident::create(['student_id' => $student->id, 'incident_date' => '2026-01-01', 'type' => 'tardiness', 'description' => 'Old']);
        DisciplineIncident::create(['student_id' => $student->id, 'incident_date' => '2026-06-01', 'type' => 'tardiness', 'description' => 'New']);

        $descriptions = $student->fresh()->disciplineIncidents->pluck('description')->toArray();

        $this->assertEquals(['New', 'Old'], $descriptions);
    }
}
