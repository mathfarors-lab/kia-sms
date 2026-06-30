<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'exists:users,id'],
            'subject'      => ['required', 'string', 'max:255'],
            'body'         => ['required', 'string', 'max:5000'],
        ];
    }
}
