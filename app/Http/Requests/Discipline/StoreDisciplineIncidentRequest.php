<?php

namespace App\Http\Requests\Discipline;

use App\Models\DisciplineIncident;
use Illuminate\Foundation\Http\FormRequest;

class StoreDisciplineIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('discipline.manage');
    }

    public function rules(): array
    {
        return [
            'incident_date' => ['required', 'date'],
            'type' => ['required', 'in:'.implode(',', DisciplineIncident::TYPES)],
            'description' => ['required', 'string', 'max:2000'],
            'action_taken' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
