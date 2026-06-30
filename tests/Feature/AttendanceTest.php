<?php

namespace Tests\Feature;

use App\Jobs\SendAbsenceAlerts;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Section;
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
}
