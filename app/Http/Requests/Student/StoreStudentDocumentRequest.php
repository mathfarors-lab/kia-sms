<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controller calls $this->authorize() explicitly
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:150'],
            'file'  => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
