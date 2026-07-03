<?php

namespace App\Http\Requests\Subject;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubjectRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name_en'     => ['required', 'string', 'max:255'],
            'name_km'     => ['nullable', 'string', 'max:255'],
            'code'        => ['required', 'string', 'max:50', 'unique:subjects,code,' . $this->route('subject')->id],
            'full_mark'   => ['nullable', 'integer', 'min:1'],
            'coefficient' => ['nullable', 'numeric', 'min:0.01', 'max:99.99'],
        ];
    }
}
