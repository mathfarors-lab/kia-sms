<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExamMarksExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        private Collection $students,
        private Collection $subjects,
        private Collection $marks,
    ) {}

    public function collection()
    {
        return $this->students;
    }

    public function headings(): array
    {
        return array_merge(['Student'], $this->subjects->map(fn ($s) => $s->name_en)->all());
    }

    public function map($student): array
    {
        $row = [$student->name_en];

        foreach ($this->subjects as $subject) {
            $row[] = $this->marks[$student->id][$subject->id]->score ?? '';
        }

        return $row;
    }
}
