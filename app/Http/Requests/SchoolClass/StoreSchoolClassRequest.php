<?php

namespace App\Http\Requests\SchoolClass;

use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolClassRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'level'         => ['nullable', 'string', 'max:255'],
            'capacity'      => ['nullable', 'integer', 'min:1'],
            'next_class_id' => ['nullable', 'exists:school_classes,id'],
        ];
    }
}
