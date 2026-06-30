<?php

namespace App\Http\Requests\Subject;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name_en'   => ['required', 'string', 'max:255'],
            'name_km'   => ['nullable', 'string', 'max:255'],
            'code'      => ['required', 'string', 'max:50', 'unique:subjects,code'],
            'full_mark' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
