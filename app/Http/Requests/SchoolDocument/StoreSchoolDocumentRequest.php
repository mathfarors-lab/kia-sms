<?php

namespace App\Http\Requests\SchoolDocument;

use App\Models\SchoolDocument;
use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controller calls $this->authorize() explicitly
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'category' => ['required', 'in:'.implode(',', SchoolDocument::CATEGORIES)],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'],
            'all_branches' => ['nullable', 'boolean'],
        ];
    }
}
