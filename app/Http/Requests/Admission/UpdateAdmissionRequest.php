<?php

namespace App\Http\Requests\Admission;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admissions.manage');
    }

    public function rules(): array
    {
        return [
            'name_en'           => ['required', 'string', 'max:150'],
            'name_km'           => ['nullable', 'string', 'max:150'],
            'gender'            => ['required', 'in:male,female,other'],
            'date_of_birth'     => ['nullable', 'date', 'before:today'],
            'address'           => ['nullable', 'string', 'max:1000'],
            'guardian_name'     => ['nullable', 'string', 'max:150'],
            'guardian_phone'    => ['nullable', 'string', 'max:20'],
            'guardian_relation' => ['nullable', 'string', 'max:50'],
            'desired_class_id'  => ['nullable', 'exists:school_classes,id'],
            'academic_year_id'  => ['nullable', 'exists:academic_years,id'],
            'notes'             => ['nullable', 'string', 'max:2000'],
            'document'          => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
