<?php

namespace Tests\Feature;

use App\Jobs\SendAbsenceAlerts;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Section $section;
    protected Student $student1;
    protected Student $student2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');

        $class = SchoolClass::create(['name' => 'Grade 10', 'level' => 'High School', 'capacity' => 30]);
        $this->section = Section::create(['school_class_id' => $class->id, 'name' => 'Section A']);

        $this->student1 = Student::create([
            'student_code' => 'KIA-001', 'name_en' => 'Alice', 'gender' => 'female', 'status' => 'enrolled',
        ]);
        $this->student2 = Student::create([
            'student_code' => 'KIA-002', 'name_en' => 'Bob', 'gender' => 'male', 'status' => 'enrolled',
        ]);
    }

    public function test_admin_can_mark_attendance_for_a_section(): void
    {
        $response = $this->actingAs($this->admin)->post(route('attendance.store', $this->section), [
            'section_id' => $this->section->id,
            'date'       => today()->toDateString(),
            'rows'       => [
                ['student_id' => $this->student1->id, 'status' => 'present', 'remark' => ''],
                ['student_id' => $this->student2->id, 'status' => 'absent',  'remark' => ''],
            ],
        ]);

        $response->assertRedirect(route('attendance.index'));
    }

    public function test_marking_creates_one_record_per_student(): void
    {
        $this->actingAs($this->admin)->post(route('attendance.store', $this->section), [
            'section_id' => $this->section->id,
            'date'       => today()->toDateString(),
            'rows'       => [
                ['student_id' => $this->student1->id, 'status' => 'present', 'remark' => ''],
                ['student_id' => $this->student2->id, 'status' => 'present', 'remark' => ''],
            ],
        ]);

        $this->assertCount(2, Attendance::all());
    }

    public function test_marking_absent_dispatches_send_absence_alerts_job(): void
    {
        Queue::fake();

        $this->actingAs($this->admin)->post(route('attendance.store', $this->section), [
            'section_id' => $this->section->id,
            'date'       => today()->toDateString(),
            'rows'       => [
                ['student_id' => $this->student1->id, 'status' => 'present', 'remark' => ''],
                ['student_id' => $this->student2->id, 'status' => 'absent',  'remark' => ''],
            ],
        ]);

        Queue::assertPushed(SendAbsenceAlerts::class, function ($job) {
            return in_array($this->student2->id, $job->studentIds)
                && $job->sectionId === $this->section->id;
        });
    }

    public function test_duplicate_mark_on_same_day_upserts(): void
    {
        $payload = [
            'section_id' => $this->section->id,
            'date'       => today()->toDateString(),
            'rows'       => [
                ['student_id' => $this->student1->id, 'status' => 'present', 'remark' => ''],
            ],
        ];

        $this->actingAs($this->admin)->post(route('attendance.store', $this->section), $payload);

        // Mark again — should upsert, not duplicate
        $payload['rows'][0]['status'] = 'absent';
        $this->actingAs($this->admin)
             ->post(route('attendance.store', $this->section), $payload)
             ->assertRedirect(route('attendance.index'));

        $this->assertCount(1, Attendance::all());
        $this->assertDatabaseHas('attendances', ['student_id' => $this->student1->id, 'status' => 'absent']);
    }

    // ── Section scoping (mark form roster + section-access authorization) ────

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

    public function test_mark_form_shows_only_that_sections_roster_not_every_enrolled_student(): void
    {
        $year = AcademicYear::create([
            'name' => '2026-2027', 'start_date' => '2026-08-01', 'end_date' => '2027-05-31', 'is_active' => true,
        ]);
        $this->student1->sections()->attach($this->section->id, ['academic_year_id' => $year->id]);
        $this->student2->sections()->attach($this->section->id, ['academic_year_id' => $year->id]);

        $otherClass = SchoolClass::create(['name' => 'Grade 11', 'level' => 'High School', 'capacity' => 30]);
        $otherSection = Section::create(['school_class_id' => $otherClass->id, 'name' => 'Section A']);
        $elsewhere = Student::create(['student_code' => 'KIA-003', 'name_en' => 'Carol', 'gender' => 'female', 'status' => 'enrolled']);
        $elsewhere->sections()->attach($otherSection->id, ['academic_year_id' => $year->id]);

        $response = $this->actingAs($this->admin)->get(route('attendance.mark', $this->section));

        $response->assertOk();
        $response->assertSee('Alice');
        $response->assertSee('Bob');
        $response->assertDontSee('Carol');
    }

    public function test_teacher_sees_only_their_own_sections_roster(): void
    {
        $year = AcademicYear::create([
            'name' => '2026-2027', 'start_date' => '2026-08-01', 'end_date' => '2027-05-31', 'is_active' => true,
        ]);
        $this->student1->sections()->attach($this->section->id, ['academic_year_id' => $year->id]);
        $teacher = $this->makeTeacherWithHomeroom($this->section);

        $response = $this->actingAs($teacher)->get(route('attendance.mark', $this->section));

        $response->assertOk();
        $response->assertSee('Alice');
    }

    public function test_teacher_cannot_view_mark_form_for_a_section_they_dont_teach(): void
    {
        $otherClass = SchoolClass::create(['name' => 'Grade 11', 'level' => 'High School', 'capacity' => 30]);
        $otherSection = Section::create(['school_class_id' => $otherClass->id, 'name' => 'Section A']);
        $teacher = $this->makeTeacherWithHomeroom($otherSection);

        $this->actingAs($teacher)
             ->get(route('attendance.mark', $this->section))
             ->assertForbidden();
    }

    public function test_teacher_cannot_submit_attendance_for_a_section_they_dont_teach(): void
    {
        $otherClass = SchoolClass::create(['name' => 'Grade 11', 'level' => 'High School', 'capacity' => 30]);
        $otherSection = Section::create(['school_class_id' => $otherClass->id, 'name' => 'Section A']);
        $teacher = $this->makeTeacherWithHomeroom($otherSection);

        $response = $this->actingAs($teacher)->post(route('attendance.store', $this->section), [
            'section_id' => $this->section->id,
            'date'       => today()->toDateString(),
            'rows'       => [
                ['student_id' => $this->student1->id, 'status' => 'present', 'remark' => ''],
            ],
        ]);

        $response->assertForbidden();
        $this->assertCount(0, Attendance::all());
    }

    public function test_principal_can_still_mark_attendance_for_any_section(): void
    {
        $principal = User::factory()->create(['status' => 'active']);
        $principal->assignRole('principal');

        $this->actingAs($principal)
             ->get(route('attendance.mark', $this->section))
             ->assertOk();
    }

    // ── Index picker scoping ────────────────────────────────────────────────
    // The index is a staff tool for picking a section to manage, gated the
    // same as the sidebar link that leads to it (attendance.mark) — not the
    // broader attendance.view that student/parent hold for their OWN record.

    public function test_teacher_sees_only_their_own_sections_in_the_index(): void
    {
        $otherClass = SchoolClass::create(['name' => 'Grade 11', 'level' => 'High School', 'capacity' => 30]);
        Section::create(['school_class_id' => $otherClass->id, 'name' => 'Section B']);
        $teacher = $this->makeTeacherWithHomeroom($this->section);

        $response = $this->actingAs($teacher)->get(route('attendance.index'));

        $response->assertOk();
        $response->assertSee($this->section->schoolClass->name);
        $response->assertDontSee('Grade 11');
    }

    public function test_admin_sees_every_section_in_the_index(): void
    {
        $otherClass = SchoolClass::create(['name' => 'Grade 11', 'level' => 'High School', 'capacity' => 30]);
        Section::create(['school_class_id' => $otherClass->id, 'name' => 'Section B']);

        $response = $this->actingAs($this->admin)->get(route('attendance.index'));

        $response->assertOk();
        $response->assertSee($this->section->schoolClass->name);
        $response->assertSee('Grade 11');
    }

    public function test_student_cannot_reach_the_staff_attendance_index(): void
    {
        $student = User::factory()->create(['status' => 'active']);
        $student->assignRole('student');

        $this->actingAs($student)
             ->get(route('attendance.index'))
             ->assertForbidden();
    }

    public function test_parent_cannot_reach_the_staff_attendance_index(): void
    {
        $parent = User::factory()->create(['status' => 'active']);
        $parent->assignRole('parent');

        $this->actingAs($parent)
             ->get(route('attendance.index'))
             ->assertForbidden();
    }
}
