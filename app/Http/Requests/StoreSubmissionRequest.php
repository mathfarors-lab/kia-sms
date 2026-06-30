<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:2000'],
            'file' => [
                'nullable', 'file',
                'max:10240', // 10 MB
                'mimes:pdf,doc,docx,png,jpg,jpeg',
            ],
        ];
    }
}
