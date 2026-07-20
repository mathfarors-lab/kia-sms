<?php

namespace App\Http\Requests\Feedback;

use App\Models\FeedbackItem;
use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category'   => 'required|in:' . implode(',', FeedbackItem::CATEGORIES),
            'subject'    => 'required|string|max:150',
            'body'       => 'required|string|max:5000',
            'student_id' => 'nullable|exists:students,id',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }
}
