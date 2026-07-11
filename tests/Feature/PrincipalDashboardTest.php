<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrincipalDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makePrincipal(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('principal');
        return $user;
    }

    private function makeStudent(string $status = 'enrolled'): Student
    {
        return Student::create([
            'name_en' => 'Student-' . uniqid(), 'student_code' => 'S-' . uniqid(),
            'gender' => 'male', 'status' => $status,
        ]);
    }

    public function test_dashboard_shows_correct_enrolled_count(): void
    {
        $principal = $this->makePrincipal();

        $this->makeStudent('enrolled');
        $this->makeStudent('enrolled');
        $this->makeStudent('enrolled');
        $this->makeStudent('dropped'); // must not count toward "enrolled"
        $this->makeStudent('graduated'); // must not count toward "enrolled"

        $html = $this->actingAs($principal)
            ->get(route('dashboard.principal'))
            ->assertOk()
            ->getContent();

        // Total students = 5, enrolled = 3 — both must render, distinctly.
        $this->assertStringContainsString('>5<', $html);
        $this->assertMatchesRegularExpression('/pill pill-ok"[^>]*>\s*3\s*enrolled/s', $html);
    }

    public function test_dashboard_shows_zero_enrolled_when_none_are_enrolled(): void
    {
        $principal = $this->makePrincipal();
        $this->makeStudent('dropped');

        $html = $this->actingAs($principal)
            ->get(route('dashboard.principal'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/pill pill-ok"[^>]*>\s*0\s*enrolled/s', $html);
    }

    // ── Admissions panel is live (Phase 1.1 of the completion roadmap) ───────────

    public function test_admissions_panel_shows_live_pending_applications(): void
    {
        $principal = $this->makePrincipal();

        $pending = \App\Models\AdmissionApplication::create([
            'application_no' => 'ADM-26-0001', 'name_en' => 'Pending Applicant',
            'gender' => 'female', 'status' => 'applied',
        ]);
        // Terminal states must NOT appear in the pending panel.
        \App\Models\AdmissionApplication::create([
            'application_no' => 'ADM-26-0002', 'name_en' => 'Rejected Applicant',
            'gender' => 'male', 'status' => 'rejected',
        ]);

        $html = $this->actingAs($principal)
            ->get(route('dashboard.principal'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Pending Applicant', $html);
        $this->assertStringContainsString(route('admissions.show', $pending), $html);
        $this->assertStringNotContainsString('Rejected Applicant', $html);
        $this->assertStringNotContainsString('Phase 2', $html);
    }
}
