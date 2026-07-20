<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('staff-evaluations.manage');
    }

    public function rules(): array
    {
        return [
            'evaluation_date' => ['required', 'date'],
            'overall_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'strengths' => ['nullable', 'string', 'max:2000'],
            'areas_for_improvement' => ['nullable', 'string', 'max:2000'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
