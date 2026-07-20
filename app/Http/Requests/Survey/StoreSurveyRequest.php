<?php

namespace App\Http\Requests\Survey;

use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Foundation\Http\FormRequest;

class StoreSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('surveys.manage');
    }

    public function rules(): array
    {
        return [
            'title_en' => 'required|string|max:150',
            'title_km' => 'nullable|string|max:150',
            'description_en' => 'nullable|string|max:2000',
            'description_km' => 'nullable|string|max:2000',
            'audience' => 'required|in:'.implode(',', Survey::AUDIENCES),
            'target_id' => 'nullable|integer',
            'is_anonymous' => 'nullable|boolean',
            'opens_at' => 'nullable|date',
            'closes_at' => 'nullable|date|after:opens_at',

            'questions' => 'required|array|min:1',
            'questions.*.type' => 'required|in:'.implode(',', SurveyQuestion::TYPES),
            'questions.*.question_text_en' => 'required|string|max:500',
            'questions.*.question_text_km' => 'nullable|string|max:500',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*' => 'nullable|string|max:150',
            'questions.*.required' => 'nullable|boolean',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Audience targeting must not exceed what this project's branch
            // model supports: reaching everyone, or a branch other than your
            // own, is cross-branch and owner-only.
            $user = $this->user();
            if ($user->hasRole('owner')) {
                return;
            }

            if ($this->input('audience') === 'all') {
                $validator->errors()->add('audience', __('surveys.all_audience_owner_only'));

                return;
            }

            if ($this->input('audience') === 'branch' && (int) $this->input('target_id') !== (int) $user->branch_id) {
                $validator->errors()->add('audience', __('surveys.other_branch_owner_only'));
            }
        });

        $validator->after(function ($validator) {
            foreach ($this->input('questions', []) as $i => $q) {
                if (($q['type'] ?? null) === 'multiple_choice' && count(array_filter($q['options'] ?? [])) < 2) {
                    $validator->errors()->add("questions.$i.options", __('surveys.needs_two_options'));
                }
            }
        });
    }
}
