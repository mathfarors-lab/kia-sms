<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_view_students_list(): void
    {
        $this->actingAs($this->admin)
             ->get(route('students.index'))
             ->assertStatus(200)
             ->assertSee('Students');
    }

    public function test_admin_can_create_student(): void
    {
        $response = $this->actingAs($this->admin)->post(route('students.store'), [
            'name_en'  => 'Test Student',
            'name_km'  => ' សិស្ស​តេស្ត',
            'gender'   => 'male',
            'status'   => 'enrolled',
            'address'  => 'Phnom Penh',
        ]);

        $student = Student::where('name_en', 'Test Student')->first();
        $this->assertNotNull($student);
        $this->assertStringStartsWith('KIA-', $student->student_code);
        $response->assertRedirect(route('students.show', $student));
    }

    public function test_student_code_is_auto_generated(): void
    {
        $this->actingAs($this->admin)->post(route('students.store'), [
            'name_en' => 'First Student',
            'gender'  => 'female',
            'status'  => 'enrolled',
        ]);

        $this->actingAs($this->admin)->post(route('students.store'), [
            'name_en' => 'Second Student',
            'gender'  => 'male',
            'status'  => 'enrolled',
        ]);

        $codes = Student::pluck('student_code');
        $this->assertCount(2, $codes->unique());
    }

    public function test_teacher_cannot_delete_student(): void
    {
        $teacher = User::factory()->create(['status' => 'active']);
        $teacher->assignRole('teacher');

        $student = Student::factory()->create();

        $this->actingAs($teacher)
             ->delete(route('students.destroy', $student))
             ->assertStatus(403);
    }

    public function test_admin_can_update_student(): void
    {
        $student = Student::factory()->create(['name_en' => 'Old Name']);

        $this->actingAs($this->admin)
             ->patch(route('students.update', $student), [
                 'name_en' => 'New Name',
                 'gender'  => 'female',
                 'status'  => 'enrolled',
             ])
             ->assertRedirect(route('students.show', $student));

        $this->assertDatabaseHas('students', ['id' => $student->id, 'name_en' => 'New Name']);
    }

    public function test_admin_can_soft_delete_student(): void
    {
        $student = Student::factory()->create();

        $response = $this->actingAs($this->admin)
             ->delete(route('students.destroy', $student));

        $response->assertRedirect(route('students.index'));
        $this->assertSoftDeleted('students', ['id' => $student->id]);
    }

    // ── Teacher scoping ──────────────────────────────────────────────────────
    // students.view is shared with accountant/receptionist/librarian, who
    // legitimately need every student. Teacher is the one role that must be
    // scoped to their own homeroom + subject-taught sections.

    public function test_teacher_sees_only_students_in_their_own_section(): void
    {
        $year = AcademicYear::create([
            'name' => '2026-2027', 'start_date' => '2026-08-01', 'end_date' => '2027-05-31', 'is_active' => true,
        ]);

        $teacherUser = User::factory()->create(['status' => 'active']);
        $teacherUser->assignRole('teacher');
        $teacherStaff = Staff::create([
            'user_id' => $teacherUser->id, 'staff_code' => 'ST-'.uniqid(), 'position' => 'Teacher', 'department' => 'Academics',
        ]);

        $ownClass = SchoolClass::create(['name' => 'Grade 7', 'level' => 'Grade 7', 'capacity' => 30]);
        $ownSection = Section::create(['school_class_id' => $ownClass->id, 'name' => 'A', 'class_teacher_id' => $teacherStaff->id]);

        $otherClass = SchoolClass::create(['name' => 'Grade 8', 'level' => 'Grade 8', 'capacity' => 30]);
        $otherSection = Section::create(['school_class_id' => $otherClass->id, 'name' => 'A']);

        $ownStudent = Student::factory()->create(['name_en' => 'Own Student']);
        $ownStudent->sections()->attach($ownSection->id, ['academic_year_id' => $year->id]);

        $otherStudent = Student::factory()->create(['name_en' => 'Other Student']);
        $otherStudent->sections()->attach($otherSection->id, ['academic_year_id' => $year->id]);

        $response = $this->actingAs($teacherUser)->get(route('students.index'));

        $response->assertOk();
        $response->assertSee('Own Student');
        $response->assertDontSee('Other Student');
    }

    public function test_teacher_with_multiple_sections_sees_students_from_all_of_them(): void
    {
        // Homeroom of one section UNION subject-taught of a second, unrelated
        // section — the list must union both, not just the homeroom half.
        $year = AcademicYear::create([
            'name' => '2026-2027', 'start_date' => '2026-08-01', 'end_date' => '2027-05-31', 'is_active' => true,
        ]);

        $teacherUser = User::factory()->create(['status' => 'active']);
        $teacherUser->assignRole('teacher');
        $teacherStaff = Staff::create([
            'user_id' => $teacherUser->id, 'staff_code' => 'ST-'.uniqid(), 'position' => 'Teacher', 'department' => 'Academics',
        ]);

        $homeroomClass = SchoolClass::create(['name' => 'Grade 7', 'level' => 'Grade 7', 'capacity' => 30]);
        $homeroomSection = Section::create(['school_class_id' => $homeroomClass->id, 'name' => 'A', 'class_teacher_id' => $teacherStaff->id]);

        $taughtClass = SchoolClass::create(['name' => 'Grade 9', 'level' => 'Grade 9', 'capacity' => 30]);
        $taughtSection = Section::create(['school_class_id' => $taughtClass->id, 'name' => 'A']);
        $subject = Subject::create(['name_en' => 'Math', 'name_km' => 'M', 'code' => 'M1', 'full_mark' => 100, 'coefficient' => 1]);
        ClassSubject::create(['school_class_id' => $taughtClass->id, 'subject_id' => $subject->id, 'teacher_id' => $teacherStaff->id]);

        $unrelatedClass = SchoolClass::create(['name' => 'Grade 11', 'level' => 'Grade 11', 'capacity' => 30]);
        $unrelatedSection = Section::create(['school_class_id' => $unrelatedClass->id, 'name' => 'A']);

        $homeroomStudent = Student::factory()->create(['name_en' => 'Homeroom Student']);
        $homeroomStudent->sections()->attach($homeroomSection->id, ['academic_year_id' => $year->id]);

        $taughtStudent = Student::factory()->create(['name_en' => 'Taught Student']);
        $taughtStudent->sections()->attach($taughtSection->id, ['academic_year_id' => $year->id]);

        $unrelatedStudent = Student::factory()->create(['name_en' => 'Unrelated Student']);
        $unrelatedStudent->sections()->attach($unrelatedSection->id, ['academic_year_id' => $year->id]);

        $response = $this->actingAs($teacherUser)->get(route('students.index'));

        $response->assertOk();
        $response->assertSee('Homeroom Student');
        $response->assertSee('Taught Student');
        $response->assertDontSee('Unrelated Student');
    }

    public function test_teacher_with_no_assigned_section_sees_no_students(): void
    {
        $teacherUser = User::factory()->create(['status' => 'active']);
        $teacherUser->assignRole('teacher');
        Staff::create([
            'user_id' => $teacherUser->id, 'staff_code' => 'ST-'.uniqid(), 'position' => 'Teacher', 'department' => 'Academics',
        ]);

        Student::factory()->create(['name_en' => 'Unrelated Student']);

        $this->actingAs($teacherUser)
             ->get(route('students.index'))
             ->assertOk()
             ->assertDontSee('Unrelated Student');
    }

    public function test_admin_still_sees_every_student_regardless_of_section(): void
    {
        Student::factory()->create(['name_en' => 'Unassigned Student']);

        $this->actingAs($this->admin)
             ->get(route('students.index'))
             ->assertOk()
             ->assertSee('Unassigned Student');
    }
}
