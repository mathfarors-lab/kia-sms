<?php

namespace Tests\Feature;

use App\Exports\SurveyResultsExport;
use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyCompletion;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Notifications\SurveyPublished;
use App\Services\SurveyService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SurveyTest extends TestCase
{
    use RefreshDatabase;

    protected AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->year = AcademicYear::create([
            'name' => 'Y1', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30', 'is_active' => true,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeUser(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }

    private function makeSurvey(array $overrides = []): Survey
    {
        return Survey::create(array_merge([
            'title_en' => 'Survey '.uniqid(),
            'created_by' => $this->makeUser('admin')->id,
            'audience' => 'all',
            'target_id' => null,
            'is_anonymous' => false,
            'status' => 'open',
            'opens_at' => now()->subDay(),
        ], $overrides));
    }

    private function makeQuestion(Survey $survey, array $overrides = []): SurveyQuestion
    {
        return SurveyQuestion::create(array_merge([
            'survey_id' => $survey->id,
            'order' => 0,
            'type' => 'free_text',
            'question_text_en' => 'Question '.uniqid(),
            'required' => true,
        ], $overrides));
    }

    private function makeStudentWithSection(): array
    {
        static $i = 0;
        $i++;
        $class = SchoolClass::create(['name' => 'Grade-'.$i, 'level' => 'High', 'capacity' => 30]);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);

        $studentUser = User::factory()->create(['status' => 'active']);
        $studentUser->assignRole('student');
        $student = Student::create([
            'user_id' => $studentUser->id, 'student_code' => 'K-'.uniqid(),
            'name_en' => 'Student-'.uniqid(), 'name_km' => 'សិស្ស', 'gender' => 'female', 'status' => 'enrolled',
        ]);

        DB::table('student_section')->insert([
            'student_id' => $student->id, 'section_id' => $section->id,
            'academic_year_id' => $this->year->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $parentUser = User::factory()->create(['status' => 'active']);
        $parentUser->assignRole('parent');
        DB::table('student_guardian')->insert([
            'student_id' => $student->id, 'guardian_id' => $parentUser->id,
            'relation' => 'parent', 'is_primary' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return compact('class', 'section', 'studentUser', 'parentUser');
    }

    // ── Anonymity (structural, model-level) ─────────────────────────────────

    public function test_anonymous_survey_response_has_no_respondent_link(): void
    {
        $survey = $this->makeSurvey(['is_anonymous' => true]);
        $question = $this->makeQuestion($survey);
        $respondent = $this->makeUser('teacher');

        $this->actingAs($respondent)->post(route('surveys.submit', $survey), [
            'answers' => [$question->id => 'My honest answer'],
        ])->assertRedirect();

        $response = SurveyResponse::where('survey_id', $survey->id)->firstOrFail();

        // Model-level assertion, not a view check: the column itself is null.
        $this->assertNull($response->respondent_id);
        $this->assertNull($response->getRawOriginal('respondent_id'));

        // The completion IS tracked (so duplicate-prevention/rate still works)
        // but carries no link to this response or its answers.
        $this->assertTrue(
            SurveyCompletion::where('survey_id', $survey->id)->where('user_id', $respondent->id)->exists()
        );
    }

    public function test_non_anonymous_survey_response_has_respondent_link(): void
    {
        $survey = $this->makeSurvey(['is_anonymous' => false]);
        $question = $this->makeQuestion($survey);
        $respondent = $this->makeUser('teacher');

        $this->actingAs($respondent)->post(route('surveys.submit', $survey), [
            'answers' => [$question->id => 'Signed answer'],
        ]);

        $response = SurveyResponse::where('survey_id', $survey->id)->firstOrFail();
        $this->assertEquals($respondent->id, $response->respondent_id);
    }

    // ── Duplicate submission — DB constraint, not just app logic ────────────

    public function test_duplicate_completion_insert_is_rejected_by_the_db_constraint(): void
    {
        $survey = $this->makeSurvey();
        $user = $this->makeUser('teacher');

        // Bypasses any app-level pre-check entirely — proves the UNIQUE
        // constraint itself is what stops a duplicate, not sequential retry logic.
        SurveyCompletion::create(['survey_id' => $survey->id, 'user_id' => $user->id, 'completed_at' => now()]);

        $this->expectException(UniqueConstraintViolationException::class);
        SurveyCompletion::create(['survey_id' => $survey->id, 'user_id' => $user->id, 'completed_at' => now()]);
    }

    public function test_duplicate_submission_via_http_shows_a_friendly_error(): void
    {
        $survey = $this->makeSurvey();
        $question = $this->makeQuestion($survey);
        $user = $this->makeUser('teacher');

        $this->actingAs($user)->post(route('surveys.submit', $survey), ['answers' => [$question->id => 'first']])
            ->assertRedirect();

        $response = $this->actingAs($user)->post(route('surveys.submit', $survey), ['answers' => [$question->id => 'second']]);

        $response->assertSessionHasErrors('survey');
        $this->assertEquals(1, SurveyResponse::where('survey_id', $survey->id)->count());
    }

    public function test_already_completed_survey_cannot_be_reopened_for_taking(): void
    {
        $survey = $this->makeSurvey();
        $this->makeQuestion($survey);
        $user = $this->makeUser('teacher');

        SurveyCompletion::create(['survey_id' => $survey->id, 'user_id' => $user->id, 'completed_at' => now()]);

        $this->actingAs($user)->get(route('surveys.take', $survey))->assertForbidden();
    }

    // ── Closed survey ────────────────────────────────────────────────────────

    public function test_closed_survey_rejects_new_submissions(): void
    {
        $survey = $this->makeSurvey(['status' => 'closed']);
        $question = $this->makeQuestion($survey);
        $user = $this->makeUser('teacher');

        $this->actingAs($user)->get(route('surveys.take', $survey))->assertForbidden();
        $this->actingAs($user)->post(route('surveys.submit', $survey), ['answers' => [$question->id => 'x']])->assertForbidden();
    }

    public function test_survey_past_closes_at_rejects_submissions_even_if_still_marked_open(): void
    {
        $survey = $this->makeSurvey(['status' => 'open', 'closes_at' => now()->subHour()]);
        $this->makeQuestion($survey);
        $user = $this->makeUser('teacher');

        $this->assertFalse($survey->isOpenForSubmissions());
        $this->actingAs($user)->get(route('surveys.take', $survey))->assertForbidden();
    }

    // ── Audience targeting — exact intended set ─────────────────────────────

    public function test_audience_all_targets_every_active_user(): void
    {
        $survey = $this->makeSurvey(['audience' => 'all']);
        $teacher = $this->makeUser('teacher');
        $accountant = $this->makeUser('accountant');

        $recipients = app(SurveyService::class)->recipientQuery($survey)->pluck('id');

        $this->assertTrue($recipients->contains($teacher->id));
        $this->assertTrue($recipients->contains($accountant->id));
    }

    public function test_audience_role_targets_only_that_role(): void
    {
        $teacherRole = Role::where('name', 'teacher')->firstOrFail();
        $survey = $this->makeSurvey(['audience' => 'role', 'target_id' => $teacherRole->id]);

        $teacher = $this->makeUser('teacher');
        $accountant = $this->makeUser('accountant');

        $recipients = app(SurveyService::class)->recipientQuery($survey)->pluck('id');

        $this->assertTrue($recipients->contains($teacher->id));
        $this->assertFalse($recipients->contains($accountant->id));
    }

    public function test_audience_branch_targets_only_that_branch(): void
    {
        $branchA = Branch::create(['name_en' => 'Branch A', 'code' => 'BRA', 'is_active' => true]);
        $branchB = Branch::create(['name_en' => 'Branch B', 'code' => 'BRB', 'is_active' => true]);

        $userA = $this->makeUser('teacher');
        $userA->update(['branch_id' => $branchA->id]);
        $userB = $this->makeUser('teacher');
        $userB->update(['branch_id' => $branchB->id]);

        $survey = $this->makeSurvey(['audience' => 'branch', 'target_id' => $branchA->id]);

        $recipients = app(SurveyService::class)->recipientQuery($survey)->pluck('id');

        $this->assertTrue($recipients->contains($userA->id));
        $this->assertFalse($recipients->contains($userB->id));
    }

    public function test_audience_section_targets_students_and_guardians_in_that_section_only(): void
    {
        ['section' => $sectionA, 'studentUser' => $studentA, 'parentUser' => $parentA] = $this->makeStudentWithSection();
        ['studentUser' => $studentB] = $this->makeStudentWithSection();

        $survey = $this->makeSurvey(['audience' => 'section', 'target_id' => $sectionA->id]);

        $recipients = app(SurveyService::class)->recipientQuery($survey)->pluck('id');

        $this->assertTrue($recipients->contains($studentA->id));
        $this->assertTrue($recipients->contains($parentA->id));
        $this->assertFalse($recipients->contains($studentB->id));
    }

    public function test_audience_class_targets_students_and_guardians_in_that_class_only(): void
    {
        ['class' => $classA, 'studentUser' => $studentA] = $this->makeStudentWithSection();
        ['studentUser' => $studentB] = $this->makeStudentWithSection();

        $survey = $this->makeSurvey(['audience' => 'class', 'target_id' => $classA->id]);

        $recipients = app(SurveyService::class)->recipientQuery($survey)->pluck('id');

        $this->assertTrue($recipients->contains($studentA->id));
        $this->assertFalse($recipients->contains($studentB->id));
    }

    public function test_user_outside_audience_cannot_take_the_survey(): void
    {
        $teacherRole = Role::where('name', 'teacher')->firstOrFail();
        $survey = $this->makeSurvey(['audience' => 'role', 'target_id' => $teacherRole->id]);
        $this->makeQuestion($survey);

        $accountant = $this->makeUser('accountant');

        $this->actingAs($accountant)->get(route('surveys.take', $survey))->assertForbidden();
    }

    // ── Publishing / fan-out ─────────────────────────────────────────────────

    public function test_publishing_notifies_exactly_the_targeted_audience(): void
    {
        Notification::fake();

        $teacherRole = Role::where('name', 'teacher')->firstOrFail();
        $survey = $this->makeSurvey(['audience' => 'role', 'target_id' => $teacherRole->id, 'status' => 'draft']);
        $this->makeQuestion($survey);

        $teacher = $this->makeUser('teacher');
        $accountant = $this->makeUser('accountant');

        $this->actingAs($this->makeUser('admin'))->post(route('surveys.publish', $survey))->assertRedirect();

        Notification::assertSentTo($teacher, SurveyPublished::class);
        Notification::assertNotSentTo($accountant, SurveyPublished::class);
    }

    public function test_publish_requires_at_least_one_question(): void
    {
        $survey = $this->makeSurvey(['status' => 'draft']);

        $this->actingAs($this->makeUser('admin'))->post(route('surveys.publish', $survey))->assertStatus(422);
        $this->assertEquals('draft', $survey->fresh()->status);
    }

    public function test_cannot_edit_a_survey_once_published(): void
    {
        $survey = $this->makeSurvey(['status' => 'open']);

        $this->actingAs($this->makeUser('admin'))->get(route('surveys.edit', $survey))->assertForbidden();
    }

    // ── Cross-branch restriction ─────────────────────────────────────────────

    public function test_principal_cannot_target_all_audience(): void
    {
        $response = $this->actingAs($this->makeUser('principal'))->post(route('surveys.store'), [
            'title_en' => 'x', 'audience' => 'all', 'is_anonymous' => '0',
            'questions' => [['type' => 'free_text', 'question_text_en' => 'q']],
        ]);

        $response->assertSessionHasErrors('audience');
    }

    public function test_owner_can_target_all_audience(): void
    {
        $response = $this->actingAs($this->makeUser('owner'))->post(route('surveys.store'), [
            'title_en' => 'x', 'audience' => 'all', 'is_anonymous' => '0',
            'questions' => [['type' => 'free_text', 'question_text_en' => 'q']],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('surveys', ['title_en' => 'x', 'audience' => 'all']);
    }

    public function test_teacher_cannot_create_a_survey(): void
    {
        $this->actingAs($this->makeUser('teacher'))->post(route('surveys.store'), [
            'title_en' => 'x', 'audience' => 'all', 'is_anonymous' => '0',
            'questions' => [['type' => 'free_text', 'question_text_en' => 'q']],
        ])->assertForbidden();
    }

    // ── Results reconciliation ───────────────────────────────────────────────

    private function seedAnswer(SurveyResponse $response, SurveyQuestion $question, ?string $text = null, ?float $value = null): SurveyAnswer
    {
        return SurveyAnswer::create([
            'response_id' => $response->id, 'question_id' => $question->id,
            'answer_text' => $text, 'answer_value' => $value,
        ]);
    }

    private function makeResponse(Survey $survey, ?User $respondent = null): SurveyResponse
    {
        return SurveyResponse::create([
            'survey_id' => $survey->id,
            'respondent_id' => $survey->is_anonymous ? null : $respondent?->id,
            'submitted_at' => now(),
        ]);
    }

    public function test_multiple_choice_tally_matches_seeded_answers_exactly(): void
    {
        $survey = $this->makeSurvey();
        $question = $this->makeQuestion($survey, ['type' => 'multiple_choice', 'options' => ['Great', 'Okay', 'Poor']]);

        $this->seedAnswer($this->makeResponse($survey), $question, 'Great');
        $this->seedAnswer($this->makeResponse($survey), $question, 'Great');
        $this->seedAnswer($this->makeResponse($survey), $question, 'Okay');

        $response = $this->actingAs($this->makeUser('admin'))->get(route('surveys.results', $survey));
        $results = collect($response->viewData('results'))->firstWhere('question.id', $question->id);

        $this->assertEquals(['Great' => 2, 'Okay' => 1], $results['tally']);
        $this->assertEquals(3, $results['count']);
    }

    public function test_rating_scale_average_matches_seeded_answers_exactly(): void
    {
        $survey = $this->makeSurvey();
        $question = $this->makeQuestion($survey, ['type' => 'rating_scale']);

        $this->seedAnswer($this->makeResponse($survey), $question, '4', 4);
        $this->seedAnswer($this->makeResponse($survey), $question, '5', 5);
        $this->seedAnswer($this->makeResponse($survey), $question, '3', 3);

        $response = $this->actingAs($this->makeUser('admin'))->get(route('surveys.results', $survey));
        $results = collect($response->viewData('results'))->firstWhere('question.id', $question->id);

        $this->assertEquals(4.0, $results['average']); // (4+5+3)/3
    }

    public function test_free_text_list_has_zero_author_attribution_when_anonymous(): void
    {
        $survey = $this->makeSurvey(['is_anonymous' => true]);
        $question = $this->makeQuestion($survey, ['type' => 'free_text']);
        $respondent = $this->makeUser('teacher');

        $this->seedAnswer($this->makeResponse($survey, $respondent), $question, 'It was fine.');

        $response = $this->actingAs($this->makeUser('admin'))->get(route('surveys.results', $survey));
        $results = collect($response->viewData('results'))->firstWhere('question.id', $question->id);

        $this->assertCount(1, $results['answers']);
        $this->assertEquals('It was fine.', $results['answers'][0]['text']);
        $this->assertNull($results['answers'][0]['author']);
        // Not just unlabeled in this array — the respondent's name must never
        // appear anywhere in the rendered results page either.
        $response->assertDontSee($respondent->name);
    }

    public function test_free_text_list_shows_author_when_not_anonymous(): void
    {
        $survey = $this->makeSurvey(['is_anonymous' => false]);
        $question = $this->makeQuestion($survey, ['type' => 'free_text']);
        $respondent = $this->makeUser('teacher');

        $this->seedAnswer($this->makeResponse($survey, $respondent), $question, 'It was fine.');

        $response = $this->actingAs($this->makeUser('admin'))->get(route('surveys.results', $survey));
        $results = collect($response->viewData('results'))->firstWhere('question.id', $question->id);

        $this->assertEquals($respondent->name, $results['answers'][0]['author']);
    }

    public function test_response_rate_uses_completions_not_answer_content(): void
    {
        $survey = $this->makeSurvey(['audience' => 'all']);
        $u1 = $this->makeUser('teacher');
        $u2 = $this->makeUser('accountant');

        SurveyCompletion::create(['survey_id' => $survey->id, 'user_id' => $u1->id, 'completed_at' => now()]);
        SurveyCompletion::create(['survey_id' => $survey->id, 'user_id' => $u2->id, 'completed_at' => now()]);

        $response = $this->actingAs($this->makeUser('admin'))->get(route('surveys.results', $survey));

        $this->assertEquals(2, $response->viewData('completedCount'));
    }

    // ── Export ───────────────────────────────────────────────────────────────

    public function test_export_excel_produces_correct_rows(): void
    {
        Excel::fake();

        $survey = $this->makeSurvey(['is_anonymous' => false]);
        $question = $this->makeQuestion($survey, ['type' => 'free_text']);
        $respondent = $this->makeUser('teacher');
        $this->seedAnswer($this->makeResponse($survey, $respondent), $question, 'Exported answer');

        $this->actingAs($this->makeUser('admin'))
            ->get(route('surveys.export-excel', $survey))
            ->assertOk();

        Excel::assertDownloaded('survey-'.$survey->id.'-results.xlsx', function (SurveyResultsExport $export) {
            $row = $export->collection()->first();
            $mapped = $export->map($row);

            return $mapped[1] === 'Exported answer';
        });
    }

    public function test_export_pdf_returns_ok(): void
    {
        $survey = $this->makeSurvey();
        $question = $this->makeQuestion($survey, ['type' => 'free_text']);
        $this->seedAnswer($this->makeResponse($survey), $question, 'x');

        $this->actingAs($this->makeUser('admin'))->get(route('surveys.export-pdf', $survey))->assertOk();
    }
}
