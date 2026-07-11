<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('staff.edit');
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:150'],
            'email'      => ['required', 'email', "unique:users,email,{$this->route('staff')->user_id}"],
            'phone'      => ['nullable', 'string', 'max:20'],
            'role'       => ['required', 'string', 'exists:roles,name'],
            'position'   => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'joined_at'  => ['nullable', 'date'],
            'salary'     => ['nullable', 'numeric', 'min:0'],
            'photo'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ];
    }
}
