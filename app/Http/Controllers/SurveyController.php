<?php

namespace App\Http\Controllers;

use App\Http\Requests\Survey\StoreSurveyRequest;
use App\Models\Branch;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Services\SurveyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class SurveyController extends Controller
{
    public function __construct(private SurveyService $service) {}

    public function index()
    {
        $this->authorize('surveys.view');

        $surveys = Survey::with('creator')->latest()->paginate(20);

        return view('surveys.index', compact('surveys'));
    }

    public function create()
    {
        $this->authorize('surveys.manage');

        return view('surveys.create', $this->formOptions());
    }

    public function store(StoreSurveyRequest $request)
    {
        $survey = $this->saveSurvey($request, new Survey);

        return redirect()->route('surveys.show', $survey)->with('success', __('surveys.created'));
    }

    public function show(Survey $survey)
    {
        $this->authorize('surveys.view');

        $survey->load('questions', 'creator');
        $targetedCount = $this->service->recipientQuery($survey)->count();
        $completedCount = $survey->completions()->count();

        return view('surveys.show', compact('survey', 'targetedCount', 'completedCount'));
    }

    public function edit(Survey $survey)
    {
        $this->authorize('surveys.manage');
        abort_unless($survey->status === 'draft', 403, __('surveys.locked_once_published'));

        $survey->load('questions');

        return view('surveys.edit', $this->formOptions() + compact('survey'));
    }

    public function update(StoreSurveyRequest $request, Survey $survey)
    {
        abort_unless($survey->status === 'draft', 403, __('surveys.locked_once_published'));

        $this->saveSurvey($request, $survey);

        return redirect()->route('surveys.show', $survey)->with('success', __('surveys.updated'));
    }

    public function destroy(Survey $survey)
    {
        $this->authorize('surveys.manage');
        abort_unless($survey->status === 'draft', 403, __('surveys.locked_once_published'));

        $survey->delete();

        return redirect()->route('surveys.index')->with('success', __('surveys.deleted'));
    }

    public function publish(Survey $survey)
    {
        $this->authorize('surveys.manage');
        abort_unless($survey->status === 'draft', 403);
        abort_if($survey->questions()->count() === 0, 422, __('surveys.needs_questions'));

        $this->service->publish($survey);

        return redirect()->route('surveys.show', $survey)->with('success', __('surveys.published'));
    }

    public function close(Survey $survey)
    {
        $this->authorize('surveys.manage');
        abort_unless($survey->status === 'open', 403);

        $this->service->close($survey);

        return redirect()->route('surveys.show', $survey)->with('success', __('surveys.closed'));
    }

    private function saveSurvey(StoreSurveyRequest $request, Survey $survey): Survey
    {
        $data = $request->validated();
        $questions = $data['questions'];
        unset($data['questions']);

        if (! $survey->exists) {
            $data['created_by'] = Auth::id();
        }

        return DB::transaction(function () use ($survey, $data, $questions) {
            $survey->fill($data)->save();
            $survey->questions()->delete();

            foreach ($questions as $i => $q) {
                SurveyQuestion::create([
                    'survey_id' => $survey->id,
                    'order' => $i,
                    'type' => $q['type'],
                    'question_text_en' => $q['question_text_en'],
                    'question_text_km' => $q['question_text_km'] ?? null,
                    'options' => $q['type'] === 'multiple_choice'
                        ? array_values(array_filter($q['options'] ?? []))
                        : null,
                    'required' => ! empty($q['required']),
                ]);
            }

            return $survey;
        });
    }

    private function formOptions(): array
    {
        return [
            'branches' => Branch::orderBy('name_en')->get(),
            'roles' => Role::orderBy('name')->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
            'sections' => Section::with('schoolClass')->orderBy('name')->get(),
        ];
    }
}
