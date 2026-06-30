<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class MarkAttendanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'section_id'     => ['required', 'exists:sections,id'],
            'date'           => ['required', 'date'],
            'rows'           => ['required', 'array'],
            'rows.*.student_id' => ['required', 'exists:students,id'],
            'rows.*.status'     => ['required', 'in:present,absent,late,excused'],
            'rows.*.remark'     => ['nullable', 'string', 'max:500'],
        ];
    }
}
