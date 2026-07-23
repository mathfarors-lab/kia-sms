<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class TermRankingExport implements WithMultipleSheets
{
    public function __construct(
        private Collection $ranking,
        private string $title,
    ) {}

    public function sheets(): array
    {
        $sheets = [new TermRankingAllSheet($this->ranking, $this->title)];

        foreach ($this->ranking->groupBy('class_name') as $className => $rows) {
            $sheets[] = new TermRankingClassSheet(
                $rows->sortBy('class_rank')->values(),
                $className ?: 'Unknown',
                $this->title,
            );
        }

        return $sheets;
    }
}

class TermRankingAllSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(
        private Collection $rows,
        private string $periodTitle,
    ) {}

    public function title(): string
    {
        return 'Term Ranking';
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'School Rank', 'Grade Rank', 'Student Code',
            'Name (EN)', 'Name (KM)', 'Grade', 'Section', 'Roll No.',
            'Total Score', 'Average (%)', 'GPA', 'Result',
        ];
    }

    public function map($row): array
    {
        return [
            $row->school_rank,
            $row->class_rank,
            $row->student_code,
            $row->name_en,
            $row->name_km ?? '',
            $row->class_name ?? '—',
            $row->section_name ?? '—',
            $row->roll_no ?? '—',
            $row->total,
            $row->average,
            $row->gpa,
            strtoupper($row->result),
        ];
    }
}

class TermRankingClassSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(
        private Collection $rows,
        private string $className,
        private string $periodTitle,
    ) {}

    public function title(): string
    {
        // Excel sheet name max 31 chars
        return mb_substr($this->className, 0, 31);
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Grade Rank', 'School Rank', 'Student Code',
            'Name (EN)', 'Name (KM)', 'Section', 'Roll No.',
            'Total Score', 'Average (%)', 'GPA', 'Result',
        ];
    }

    public function map($row): array
    {
        return [
            $row->class_rank,
            $row->school_rank,
            $row->student_code,
            $row->name_en,
            $row->name_km ?? '',
            $row->section_name ?? '—',
            $row->roll_no ?? '—',
            $row->total,
            $row->average,
            $row->gpa,
            strtoupper($row->result),
        ];
    }
}
