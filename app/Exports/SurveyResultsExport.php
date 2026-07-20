<?php

namespace App\Exports;

use App\Models\Survey;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SurveyResultsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Survey $survey, private Collection $rows) {}

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->survey->is_anonymous
            ? ['Question', 'Answer']
            : ['Question', 'Answer', 'Respondent'];
    }

    public function map($row): array
    {
        return $this->survey->is_anonymous
            ? [$row['question'], $row['answer']]
            : [$row['question'], $row['answer'], $row['respondent'] ?? ''];
    }
}
