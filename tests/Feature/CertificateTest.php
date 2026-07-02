<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        AcademicYear::create([
            'name' => 'Test Year', 'start_date' => '2026-01-01',
            'end_date' => '2026-12-31', 'is_active' => true,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    private function makePrincipal(): User
    {
        $user = User::factory()->create();
        $user->assignRole('principal');
        return $user;
    }

    private function makeTeacher(): User
    {
        $user = User::factory()->create();
        $user->assignRole('teacher');
        return $user;
    }

    private function makeStudentUser(string $status = 'enrolled'): array
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('student');
        $student = Student::create([
            'user_id'      => $user->id,
            'name_en'      => 'Student-' . uniqid(),
            'name_km'      => null,
            'student_code' => 'S-' . uniqid(),
            'gender'       => 'female',
            'status'       => $status,
        ]);
        return [$user, $student];
    }

    // ── Enrollment Certificate ────────────────────────────────────────────────────

    /** Admin can issue enrollment certificate to an enrolled student. */
    public function test_admin_can_issue_enrollment_certificate(): void
    {
        [, $student] = $this->makeStudentUser('enrolled');

        $this->actingAs($this->makeAdmin())
            ->get(route('certificates.enrollment', $student))
            ->assertOk()
            ->assertSee($student->name_en)
            ->assertSee($student->student_code);
    }

    /** Principal can issue enrollment certificate. */
    public function test_principal_can_issue_enrollment_certificate(): void
    {
        [, $student] = $this->makeStudentUser('enrolled');

        $this->actingAs($this->makePrincipal())
            ->get(route('certificates.enrollment', $student))
            ->assertOk();
    }

    /** Teacher cannot issue certificates (no certificates.issue permission). */
    public function test_teacher_cannot_issue_certificate(): void
    {
        [, $student] = $this->makeStudentUser('enrolled');

        $this->actingAs($this->makeTeacher())
            ->get(route('certificates.enrollment', $student))
            ->assertForbidden();
    }

    /** Student cannot issue certificates. */
    public function test_student_cannot_issue_certificate(): void
    {
        [$userA, $studentA] = $this->makeStudentUser('enrolled');

        $this->actingAs($userA)
            ->get(route('certificates.enrollment', $studentA))
            ->assertForbidden();
    }

    // ── Graduation Certificate ────────────────────────────────────────────────────

    /** Admin can issue graduation certificate to a graduated student. */
    public function test_admin_can_issue_graduation_certificate(): void
    {
        [, $student] = $this->makeStudentUser('graduated');

        $this->actingAs($this->makeAdmin())
            ->get(route('certificates.graduation', $student))
            ->assertOk()
            ->assertSee($student->name_en);
    }

    /** Graduation certificate is blocked for a non-graduated (enrolled) student. */
    public function test_graduation_cert_blocked_for_enrolled_student(): void
    {
        [, $student] = $this->makeStudentUser('enrolled');

        $this->actingAs($this->makeAdmin())
            ->get(route('certificates.graduation', $student))
            ->assertStatus(403);
    }

    /** Graduation certificate is blocked for a transferred student. */
    public function test_graduation_cert_blocked_for_transferred_student(): void
    {
        [, $student] = $this->makeStudentUser('transferred');

        $this->actingAs($this->makeAdmin())
            ->get(route('certificates.graduation', $student))
            ->assertStatus(403);
    }

    // ── Leaving Certificate ───────────────────────────────────────────────────────

    /** Admin can issue leaving certificate to any student. */
    public function test_admin_can_issue_leaving_certificate(): void
    {
        [, $student] = $this->makeStudentUser('transferred');

        $this->actingAs($this->makeAdmin())
            ->get(route('certificates.leaving', $student))
            ->assertOk()
            ->assertSee($student->name_en);
    }

    /** Leaving certificate works for any student status. */
    public function test_leaving_certificate_available_for_any_status(): void
    {
        foreach (['enrolled', 'transferred', 'graduated', 'dropped'] as $status) {
            [, $student] = $this->makeStudentUser($status);

            $this->actingAs($this->makeAdmin())
                ->get(route('certificates.leaving', $student))
                ->assertOk();
        }
    }
}
