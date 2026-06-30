<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
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
}
