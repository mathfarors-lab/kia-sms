<?php

namespace App\Http\Controllers;

use App\Exports\SurveyResultsExport;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Services\SurveyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class SurveyResultController extends Controller
{
    public function __construct(private SurveyService $service) {}

    public function show(Survey $survey)
    {
        $this->authorize('surveys.view');

        $results = $this->buildResults($survey);
        $targetedCount = $this->service->recipientQuery($survey)->count();
        $completedCount = $survey->completions()->count();

        return view('surveys.results', compact('survey', 'results', 'targetedCount', 'completedCount'));
    }

    public function exportExcel(Survey $survey)
    {
        $this->authorize('surveys.view');

        return Excel::download(
            new SurveyResultsExport($survey, $this->exportRows($survey)),
            'survey-'.$survey->id.'-results.xlsx'
        );
    }

    public function exportPdf(Survey $survey)
    {
        $this->authorize('surveys.view');

        $results = $this->buildResults($survey);

        $pdf = Pdf::loadView('pdf.survey-results', compact('survey', 'results'))
            ->setPaper('a4')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        return $pdf->download('survey-'.$survey->id.'-results.pdf');
    }

    /** Per-question tallies / averages / free-text lists for the results screen. */
    private function buildResults(Survey $survey): array
    {
        $survey->load('questions');
        $results = [];

        foreach ($survey->questions as $question) {
            $answers = SurveyAnswer::where('question_id', $question->id)->get();

            $row = ['question' => $question, 'count' => $answers->count()];

            if (in_array($question->type, ['multiple_choice', 'yes_no'], true)) {
                $row['tally'] = $answers->countBy('answer_text')->sortDesc()->toArray();
            } elseif ($question->type === 'rating_scale') {
                $row['average'] = $answers->count() > 0 ? round((float) $answers->avg('answer_value'), 2) : null;
            } elseif ($question->type === 'free_text') {
                $row['answers'] = $this->freeTextAnswers($survey, $answers);
            }

            $results[] = $row;
        }

        return $results;
    }

    /**
     * For an anonymous survey this NEVER touches survey_responses.respondent_id
     * — the author field is structurally absent from the data going into the
     * view, not merely left out of the display.
     */
    private function freeTextAnswers(Survey $survey, Collection $answers): array
    {
        if ($survey->is_anonymous) {
            return $answers->pluck('answer_text')->filter()->values()
                ->map(fn ($text) => ['text' => $text, 'author' => null])
                ->toArray();
        }

        $answers->load('response.respondent');

        return $answers->filter(fn ($a) => $a->answer_text)
            ->map(fn ($a) => ['text' => $a->answer_text, 'author' => $a->response->respondent->name ?? null])
            ->values()
            ->toArray();
    }

    private function exportRows(Survey $survey): Collection
    {
        $survey->load('questions');

        $answers = SurveyAnswer::whereIn('question_id', $survey->questions->pluck('id'))
            ->with('question')
            ->when(! $survey->is_anonymous, fn ($q) => $q->with('response.respondent'))
            ->get();

        return $answers->map(function ($a) use ($survey) {
            return [
                'question' => $a->question->question_text_en,
                'answer' => $a->answer_value !== null ? (string) $a->answer_value : (string) $a->answer_text,
                'respondent' => $survey->is_anonymous ? null : ($a->response->respondent->name ?? __('surveys.no_account')),
            ];
        });
    }
}
