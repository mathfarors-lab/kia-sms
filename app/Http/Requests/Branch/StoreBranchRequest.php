<?php

namespace App\Http\Requests\Branch;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('owner');
    }

    public function rules(): array
    {
        return [
            'name_en'  => ['required', 'string', 'max:150'],
            'name_km'  => ['nullable', 'string', 'max:150'],
            'address'  => ['nullable', 'string', 'max:1000'],
            'code'     => ['required', 'string', 'max:10', 'alpha_dash', 'unique:branches,code'],
            'logo'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
            'is_active'=> ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->code) {
            $this->merge(['code' => strtoupper($this->code)]);
        }
    }
}
