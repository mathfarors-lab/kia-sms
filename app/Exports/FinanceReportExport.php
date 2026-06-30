<?php

namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FinanceReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Invoice::with(['student', 'academicYear'])->latest()->get();
    }

    public function headings(): array
    {
        return [
            'Invoice #', 'Student', 'Year', 'Term', 'Subtotal', 'Discount', 'Total', 'Paid', 'Balance', 'Status', 'Due Date',
        ];
    }

    public function map($invoice): array
    {
        return [
            $invoice->number,
            $invoice->student->name_en ?? '',
            $invoice->academicYear->name ?? '',
            $invoice->term,
            $invoice->subtotal,
            $invoice->discount,
            $invoice->total,
            $invoice->paid,
            $invoice->remainingBalance(),
            $invoice->status,
            $invoice->due_date?->format('Y-m-d') ?? '',
        ];
    }
}
