<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('students.edit');
    }

    public function rules(): array
    {
        return [
            'name_en'       => ['required', 'string', 'max:150'],
            'name_km'       => ['nullable', 'string', 'max:150'],
            'gender'        => ['required', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'address'       => ['nullable', 'string', 'max:500'],
            'status'        => ['required', 'in:enrolled,transferred,graduated,dropped'],
            'photo'         => ['nullable', 'image', 'max:2048'],
        ];
    }
}
