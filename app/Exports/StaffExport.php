<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StaffExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Collection $staff) {}

    public function collection()
    {
        return $this->staff;
    }

    public function headings(): array
    {
        return ['Code', 'Name', 'Email', 'Role', 'Position', 'Department', 'Joined'];
    }

    public function map($member): array
    {
        return [
            $member->staff_code,
            $member->user->name ?? '',
            $member->user->email ?? '',
            $member->user->getRoleNames()->implode(', '),
            $member->position ?? '',
            $member->department ?? '',
            $member->joined_at?->format('Y-m-d') ?? '',
        ];
    }
}
