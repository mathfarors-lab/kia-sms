<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHomeworkRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'section_id'  => ['required', 'exists:sections,id'],
            'subject_id'  => ['required', 'exists:subjects,id'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date'    => ['required', 'date'],
            'attachment'  => [
                'nullable', 'file',
                'max:10240', // 10 MB
                'mimes:pdf,doc,docx,png,jpg,jpeg',
            ],
        ];
    }
}
