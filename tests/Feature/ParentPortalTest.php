<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\ExamResult;
use App\Models\Invoice;
use App\Models\Section;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeParent(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('parent');
        return $user;
    }

    private function makeStudent(): Student
    {
        $studentUser = User::factory()->create(['status' => 'active']);
        return Student::create([
            'user_id'      => $studentUser->id,
            'name_en'      => 'Child ' . uniqid(),
            'name_km'      => 'កូន',
            'student_code' => 'S-' . uniqid(),
            'gender'       => 'male',
            'status'       => 'enrolled',
        ]);
    }

    private function linkChild(User $parent, Student $child): void
    {
        $parent->wards()->attach($child->id, ['relation' => 'parent', 'is_primary' => true]);
    }

    // ── Happy path ─────────────────────────────────────────────────────────────

    public function test_parent_sees_only_own_children(): void
    {
        $parent     = $this->makeParent();
        $myChild    = $this->makeStudent();
        $otherChild = $this->makeStudent();

        $this->linkChild($parent, $myChild);
        // $otherChild is NOT linked to $parent

        $this->actingAs($parent)
             ->get(route('parent.children'))
             ->assertOk()
             ->assertSee($myChild->name_en)
             ->assertDontSee($otherChild->name_en);
    }

    public function test_parent_can_view_own_childs_detail(): void
    {
        $parent = $this->makeParent();
        $child  = $this->makeStudent();
        $this->linkChild($parent, $child);

        $this->actingAs($parent)
             ->get(route('parent.child.show', $child))
             ->assertOk()
             ->assertSee($child->name_en);
    }

    // ── IDOR guard ─────────────────────────────────────────────────────────────

    public function test_parent_cannot_view_another_childs_detail(): void
    {
        $parent     = $this->makeParent();
        $myChild    = $this->makeStudent();
        $otherChild = $this->makeStudent();

        $this->linkChild($parent, $myChild);
        // $otherChild NOT linked

        $this->actingAs($parent)
             ->get(route('parent.child.show', $otherChild))
             ->assertForbidden();
    }

    // ── Published results only ─────────────────────────────────────────────────

    public function test_parent_sees_only_published_exam_results(): void
    {
        $parent = $this->makeParent();
        $child  = $this->makeStudent();
        $this->linkChild($parent, $child);

        $year = AcademicYear::create([
            'name' => 'Test Year', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        $published   = Exam::create(['academic_year_id' => $year->id, 'name' => 'Published Exam', 'type' => 'midterm', 'is_published' => true]);
        $unpublished = Exam::create(['academic_year_id' => $year->id, 'name' => 'Draft Exam', 'type' => 'final', 'is_published' => false]);

        $subject = \App\Models\Subject::create(['name_en' => 'Math', 'name_km' => 'គណិត', 'code' => 'MTH-001']);
        ExamMark::create(['exam_id' => $published->id, 'student_id' => $child->id, 'subject_id' => $subject->id, 'score' => 80]);
        ExamMark::create(['exam_id' => $unpublished->id, 'student_id' => $child->id, 'subject_id' => $subject->id, 'score' => 70]);

        $this->actingAs($parent)
             ->get(route('parent.child.show', $child))
             ->assertOk()
             ->assertSee('Published Exam')
             ->assertDontSee('Draft Exam');
    }

    // ── Gate scan arrival/departure history (M4) ─────────────────────────────────

    public function test_parent_sees_gate_scan_arrival_and_departure_times(): void
    {
        $parent = $this->makeParent();
        $child  = $this->makeStudent();
        $this->linkChild($parent, $child);

        $class   = SchoolClass::create(['name' => 'Grade 5']);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);

        Attendance::create([
            'student_id' => $child->id, 'section_id' => $section->id, 'date' => today(),
            'status' => 'present', 'method' => 'gate_scan',
            'arrival_time' => '07:12:00', 'departure_time' => '15:05:00',
        ]);

        $html = $this->actingAs($parent)
            ->get(route('parent.child.show', $child))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('7:12 AM', $html);
        $this->assertStringContainsString('3:05 PM', $html);
    }

    public function test_parent_sees_dash_when_no_gate_scan_recorded(): void
    {
        $parent = $this->makeParent();
        $child  = $this->makeStudent();
        $this->linkChild($parent, $child);

        $class   = SchoolClass::create(['name' => 'Grade 5']);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);

        // Manually-marked attendance — no arrival/departure time at all.
        Attendance::create([
            'student_id' => $child->id, 'section_id' => $section->id, 'date' => today(),
            'status' => 'present', 'method' => 'manual',
        ]);

        $this->actingAs($parent)
            ->get(route('parent.child.show', $child))
            ->assertOk()
            ->assertSee('—', false);
    }

    // ── Non-parent cannot access portal ────────────────────────────────────────

    public function test_non_parent_cannot_access_children_list(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('teacher');

        $this->actingAs($user)
             ->get(route('parent.children'))
             ->assertForbidden();
    }
}
