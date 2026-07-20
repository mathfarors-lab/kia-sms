<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AdmissionsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Collection $applications) {}

    public function collection()
    {
        return $this->applications;
    }

    public function headings(): array
    {
        return ['Application #', 'Name (EN)', 'Name (KM)', 'Desired Class', 'Guardian Phone', 'Status', 'Submitted'];
    }

    public function map($app): array
    {
        return [
            $app->application_no,
            $app->name_en,
            $app->name_km,
            $app->desiredClass->name ?? '',
            $app->guardian_phone ?? '',
            ucfirst($app->status),
            $app->created_at->format('Y-m-d'),
        ];
    }
}
