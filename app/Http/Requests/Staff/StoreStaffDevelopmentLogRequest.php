<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffDevelopmentLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controller calls $this->authorize() explicitly
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'provider' => ['nullable', 'string', 'max:150'],
            'completed_date' => ['required', 'date'],
            'hours' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
