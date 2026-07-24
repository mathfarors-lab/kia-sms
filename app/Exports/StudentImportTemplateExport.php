<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentImportTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'student_code', 'name_en', 'name_km', 'gender',
            'date_of_birth', 'address', 'class_name', 'section_name',
        ];
    }

    public function array(): array
    {
        return [
            [
                'KIA-26-0001', 'Sok Dara', 'សុខ ដារា', 'male',
                '2015-03-12', 'Phnom Penh, Cambodia', 'Grade 5', 'Section A',
            ],
        ];
    }
}
