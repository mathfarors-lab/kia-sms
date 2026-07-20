<?php

namespace App\Http\Requests\Semester;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('academic-years.manage');
    }

    public function rules(): array
    {
        $academicYear = $this->route('academicYear');

        return [
            'semester_number' => [
                'required', 'in:1,2',
                Rule::unique('semesters', 'semester_number')->where('academic_year_id', $academicYear?->id),
            ],
            'name' => ['nullable', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $academicYear = $this->route('academicYear');
            if (! $academicYear || ! $this->start_date || ! $this->end_date) {
                return;
            }

            if ($this->start_date < $academicYear->start_date->toDateString()
                || $this->end_date > $academicYear->end_date->toDateString()) {
                $validator->errors()->add('start_date', __('semester_planning.outside_year_range'));
            }
        });
    }
}
