<?php

namespace App\Http\Requests\Student;

use App\Models\StudentTransfer;
use Illuminate\Foundation\Http\FormRequest;

class WithdrawStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('students.edit');
    }

    public function rules(): array
    {
        return [
            'reason_category'     => 'required|in:' . implode(',', StudentTransfer::REASON_CATEGORIES),
            'reason_note'         => 'nullable|string|max:1000',
            'effective_date'      => 'required|date',
            'acknowledge_balance' => 'nullable|boolean',
        ];
    }
}
