<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'     => ['required', 'string', 'max:255'],
            'body_en'   => ['required', 'string'],
            'body_km'   => ['nullable', 'string'],
            'audience'  => ['required', 'in:all,class,grade'],
            'target_id' => ['nullable', 'integer'],
        ];
    }
}
