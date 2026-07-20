<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InvoicesListExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Collection $invoices) {}

    public function collection()
    {
        return $this->invoices;
    }

    public function headings(): array
    {
        return ['Invoice #', 'Student', 'Year', 'Term', 'Total', 'Paid', 'Balance', 'Status', 'Due Date'];
    }

    public function map($invoice): array
    {
        return [
            $invoice->number,
            $invoice->student->name_en ?? '',
            $invoice->academicYear->name ?? '',
            $invoice->term,
            $invoice->total,
            $invoice->paid,
            $invoice->remainingBalance(),
            ucfirst($invoice->status),
            $invoice->due_date?->format('Y-m-d') ?? '',
        ];
    }
}
