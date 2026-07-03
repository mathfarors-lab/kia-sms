<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('exams.manage');
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'name'             => ['required', 'string', 'max:200'],
            'type'             => ['required', 'in:monthly,midterm,final'],
            'semester'         => ['required', 'in:1,2'],
            'weight'           => ['required', 'numeric', 'min:0'],
        ];
    }
}
