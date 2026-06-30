<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'         => ['required', 'string', 'max:255'],
            'author'        => ['nullable', 'string', 'max:255'],
            'isbn'          => ['nullable', 'string', 'max:20', 'unique:books,isbn'],
            'category'      => ['nullable', 'string', 'max:100'],
            'total_copies'  => ['required', 'integer', 'min:1'],
            'cover'         => [
                'nullable', 'file',
                'max:5120', // 5 MB
                'mimes:png,jpg,jpeg',
            ],
        ];
    }
}
