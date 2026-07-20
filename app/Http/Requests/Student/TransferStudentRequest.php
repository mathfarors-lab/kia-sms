<?php

namespace App\Http\Requests\Student;

use App\Models\StudentTransfer;
use Illuminate\Foundation\Http\FormRequest;

class TransferStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('students.edit');
    }

    public function rules(): array
    {
        return [
            'reason_category'       => 'required|in:' . implode(',', StudentTransfer::REASON_CATEGORIES),
            'reason_note'           => 'nullable|string|max:1000',
            'effective_date'        => 'required|date',
            'destination_branch_id' => 'nullable|exists:branches,id',
            'destination_name'      => 'nullable|string|max:150',
            'acknowledge_balance'   => 'nullable|boolean',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (empty($this->destination_branch_id) && empty($this->destination_name)) {
                $validator->errors()->add('destination_branch_id', __('student_transfer.destination_required'));
            }
        });
    }
}
