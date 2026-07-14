<?php

namespace App\Http\Requests\VisitorLog;

use Illuminate\Foundation\Http\FormRequest;

class StoreVisitorLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('visitors.manage');
    }

    public function rules(): array
    {
        return [
            'visitor_name'  => ['required', 'string', 'max:150'],
            'purpose'       => ['required', 'string', 'max:255'],
            'host_staff_id' => ['nullable', 'exists:staff,id'],
        ];
    }
}
