<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\ReportComment;
use App\Models\Section;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\TermResult;
use App\Models\User;
use App\Support\BranchContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportCommentBankTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchA;
    private Branch $branchB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->branchA = Branch::findOrFail(1);
        $this->branchB = Branch::create(['name_en' => 'Riverside Campus', 'code' => 'RC', 'is_active' => true]);

        BranchContext::clear();
    }

    protected function tearDown(): void
    {
        BranchContext::clear();
        parent::tearDown();
    }

    private function makePrincipal(?Branch $branch = null): User
    {
        $user = User::factory()->create(['status' => 'active', 'branch_id' => $branch?->id]);
        $user->assignRole('principal');
        return $user;
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('admin');
        return $user;
    }

    private function makeTeacher(?Branch $branch = null): User
    {
        $user = User::factory()->create(['status' => 'active', 'branch_id' => $branch?->id]);
        $user->assignRole('teacher');
        return $user;
    }

    // ── Managing the bank ────────────────────────────────────────────────────

    public function test_principal_can_view_the_comment_bank_index(): void
    {
        $this->actingAs($this->makePrincipal())
            ->get(route('report-comments.index'))
            ->assertOk();
    }

    public function test_admin_can_view_the_comment_bank_index(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('report-comments.index'))
            ->assertOk();
    }

    public function test_principal_can_create_a_comment(): void
    {
        $this->actingAs($this->makePrincipal())
            ->post(route('report-comments.store'), [
                'category' => 'positive',
                'text_en'  => 'Shows excellent effort in class.',
                'text_km'  => 'បង្ហាញការខិតខំប្រឹងប្រែងល្អនៅក្នុងថ្នាក់។',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('report_comments', [
            'category' => 'positive',
            'text_en'  => 'Shows excellent effort in class.',
        ]);
    }

    public function test_creating_a_comment_requires_text_en(): void
    {
        $this->actingAs($this->makePrincipal())
            ->post(route('report-comments.store'), ['category' => 'positive'])
            ->assertSessionHasErrors('text_en');
    }

    public function test_principal_can_update_a_comment(): void
    {
        $principal = $this->makePrincipal($this->branchA);
        $comment = BranchContext::within($this->branchA->id, fn () => ReportComment::create([
            'category' => 'general', 'text_en' => 'Original text.',
        ]));

        $this->actingAs($principal)
            ->patch(route('report-comments.update', $comment), [
                'category' => 'general',
                'text_en'  => 'Updated text.',
            ])
            ->assertRedirect();

        $this->assertEquals('Updated text.', $comment->fresh()->text_en);
    }

    public function test_principal_can_delete_a_comment(): void
    {
        $principal = $this->makePrincipal($this->branchA);
        $comment = BranchContext::within($this->branchA->id, fn () => ReportComment::create([
            'category' => 'general', 'text_en' => 'To be removed.',
        ]));

        $this->actingAs($principal)
            ->delete(route('report-comments.destroy', $comment))
            ->assertRedirect();

        $this->assertDatabaseMissing('report_comments', ['id' => $comment->id]);
    }

    public function test_teacher_cannot_manage_the_comment_bank(): void
    {
        $teacher = $this->makeTeacher();

        $this->actingAs($teacher)->get(route('report-comments.index'))->assertForbidden();
        $this->actingAs($teacher)
            ->post(route('report-comments.store'), ['text_en' => 'Should not be allowed.'])
            ->assertForbidden();
    }

    // ── Branch scoping ───────────────────────────────────────────────────────

    public function test_comment_bank_is_branch_scoped(): void
    {
        BranchContext::within($this->branchA->id, fn () => ReportComment::create([
            'category' => 'general', 'text_en' => 'Branch A only comment.',
        ]));
        BranchContext::within($this->branchB->id, fn () => ReportComment::create([
            'category' => 'general', 'text_en' => 'Branch B only comment.',
        ]));

        $principalA = $this->makePrincipal($this->branchA);

        $html = $this->actingAs($principalA)
            ->get(route('report-comments.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Branch A only comment.', $html);
        $this->assertStringNotContainsString('Branch B only comment.', $html);
    }

    // ── Using the bank on a term result remark ──────────────────────────────

    private function makeTermResult(Branch $branch): array
    {
        return BranchContext::within($branch->id, function () {
            $year = AcademicYear::create([
                'name' => 'Y-' . uniqid(), 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
            ]);
            $class = SchoolClass::create(['name' => 'Grade 6']);
            $section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);

            $studentUser = User::factory()->create(['status' => 'active']);
            $student = Student::create([
                'user_id' => $studentUser->id, 'name_en' => 'Remark Student', 'name_km' => null,
                'student_code' => 'S-' . uniqid(), 'gender' => 'male', 'status' => 'enrolled',
            ]);
            $section->students()->attach($student->id, ['academic_year_id' => $year->id]);

            $termResult = TermResult::create([
                'academic_year_id' => $year->id, 'semester' => 1,
                'student_id' => $student->id, 'section_id' => $section->id,
            ]);

            return [$year, $student, $termResult];
        });
    }

    public function test_teacher_without_term_results_manage_cannot_edit_a_remark(): void
    {
        [$year, $student] = $this->makeTermResult($this->branchA);
        $teacher = $this->makeTeacher($this->branchA);

        $this->actingAs($teacher)
            ->get(route('term-results.remark.edit', [$year, '1', $student]))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->patch(route('term-results.remark.update', [$year, '1', $student]), ['teacher_remark' => 'Trying anyway.'])
            ->assertForbidden();
    }

    public function test_edit_remark_page_shows_bank_comments_to_pick_from(): void
    {
        [$year, $student] = $this->makeTermResult($this->branchA);
        BranchContext::within($this->branchA->id, fn () => ReportComment::create([
            'category' => 'positive', 'text_en' => 'A pleasure to teach.',
        ]));
        $principal = $this->makePrincipal($this->branchA);

        $this->actingAs($principal)
            ->get(route('term-results.remark.edit', [$year, '1', $student]))
            ->assertOk()
            ->assertSee('A pleasure to teach.');
    }

    public function test_principal_can_save_a_remark_picked_from_the_bank(): void
    {
        [$year, $student, $termResult] = $this->makeTermResult($this->branchA);
        $principal = $this->makePrincipal($this->branchA);

        $this->actingAs($principal)
            ->patch(route('term-results.remark.update', [$year, '1', $student]), [
                'teacher_remark' => 'A pleasure to teach.',
            ])
            ->assertRedirect(route('term-results.show', [$year, '1', $student]));

        $this->assertEquals('A pleasure to teach.', $termResult->fresh()->teacher_remark);
    }
}
