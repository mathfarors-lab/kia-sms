<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffQualificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controller calls $this->authorize() explicitly
    }

    public function rules(): array
    {
        return [
            'degree' => ['required', 'string', 'max:150'],
            'institution' => ['required', 'string', 'max:150'],
            'year' => ['required', 'integer', 'min:1950', 'max:'.(date('Y') + 1)],
        ];
    }
}
