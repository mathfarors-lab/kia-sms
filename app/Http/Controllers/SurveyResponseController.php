<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyCompletion;
use App\Models\SurveyResponse;
use App\Services\SurveyService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SurveyResponseController extends Controller
{
    public function __construct(private SurveyService $service) {}

    /** Open surveys this user is targeted by and hasn't completed yet — role-agnostic. */
    public function index()
    {
        $user = Auth::user();
        $completedIds = SurveyCompletion::where('user_id', $user->id)->pluck('survey_id');

        $surveys = Survey::where('status', 'open')
            ->whereNotIn('id', $completedIds)
            ->get()
            ->filter(fn (Survey $survey) => $this->service->recipientQuery($survey)->where('id', $user->id)->exists())
            ->values();

        return view('surveys.my', compact('surveys'));
    }

    public function create(Survey $survey)
    {
        $this->authorizeTake($survey);
        abort_unless($survey->isOpenForSubmissions(), 403, __('surveys.closed_message'));
        abort_if($this->alreadyCompleted($survey), 403, __('surveys.already_submitted'));

        $survey->load('questions');

        return view('surveys.take', compact('survey'));
    }

    public function store(Request $request, Survey $survey)
    {
        $this->authorizeTake($survey);
        abort_unless($survey->isOpenForSubmissions(), 403, __('surveys.closed_message'));

        $survey->load('questions');

        $rules = [];
        foreach ($survey->questions as $question) {
            $rules["answers.{$question->id}"] = $question->required ? 'required' : 'nullable';
        }
        $request->validate($rules);

        $user = Auth::user();

        try {
            DB::transaction(function () use ($survey, $request, $user) {
                // The unique constraint on [survey_id, user_id] is the real
                // guard against duplicate submissions — this insert is what
                // actually enforces "submit once," not the earlier check above.
                SurveyCompletion::create([
                    'survey_id' => $survey->id,
                    'user_id' => $user->id,
                    'completed_at' => now(),
                ]);

                $response = SurveyResponse::create([
                    'survey_id' => $survey->id,
                    // Genuinely absent from the row for an anonymous survey —
                    // never set, not merely hidden later.
                    'respondent_id' => $survey->is_anonymous ? null : $user->id,
                    'submitted_at' => now(),
                ]);

                foreach ($survey->questions as $question) {
                    $answer = $request->input("answers.{$question->id}");
                    if ($answer === null || $answer === '') {
                        continue;
                    }

                    SurveyAnswer::create([
                        'response_id' => $response->id,
                        'question_id' => $question->id,
                        'answer_text' => is_array($answer) ? null : (string) $answer,
                        'answer_value' => $question->type === 'rating_scale' ? (float) $answer : null,
                    ]);
                }
            });
        } catch (UniqueConstraintViolationException) {
            return back()->withErrors(['survey' => __('surveys.already_submitted')]);
        }

        return redirect()->route('dashboard')->with('success', __('surveys.submitted'));
    }

    private function authorizeTake(Survey $survey): void
    {
        $user = Auth::user();
        abort_unless(
            $this->service->recipientQuery($survey)->where('id', $user->id)->exists(),
            403
        );
    }

    private function alreadyCompleted(Survey $survey): bool
    {
        return SurveyCompletion::where('survey_id', $survey->id)->where('user_id', Auth::id())->exists();
    }
}
