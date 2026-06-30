<?php

namespace App\Http\Requests\Section;

use Illuminate\Foundation\Http\FormRequest;

class StoreSectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'school_class_id'  => ['required', 'exists:school_classes,id'],
            'name'             => ['required', 'string', 'max:255'],
            'class_teacher_id' => ['nullable', 'exists:staff,id'],
        ];
    }
}
