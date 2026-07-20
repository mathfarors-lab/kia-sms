<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Collection $students) {}

    public function collection()
    {
        return $this->students;
    }

    public function headings(): array
    {
        return ['Code', 'Name (EN)', 'Name (KM)', 'Gender', 'Date of Birth', 'Status'];
    }

    public function map($student): array
    {
        return [
            $student->student_code,
            $student->name_en,
            $student->name_km,
            ucfirst($student->gender),
            $student->date_of_birth?->format('Y-m-d') ?? '',
            ucfirst($student->status),
        ];
    }
}
