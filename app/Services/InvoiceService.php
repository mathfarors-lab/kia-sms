<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceSequence;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Generate invoices for every enrolled student of a class for a given term.
     * Idempotent: students who already have an invoice for this term are skipped.
     *
     * @return array{created: int, skipped: int, invoice_numbers: string[]}
     */
    public function generateForClass(SchoolClass $class, AcademicYear $year, string $term, ?Carbon $dueDate = null): array
    {
        // Fees applicable to this class (class-specific + school-wide fees)
        $fees = FeeStructure::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('school_class_id')->orWhere('school_class_id', $class->id))
            ->get();

        if ($fees->isEmpty()) {
            return ['created' => 0, 'skipped' => 0, 'invoice_numbers' => []];
        }

        // Students currently enrolled in this class for this year
        $studentIds = DB::table('student_section')
            ->join('sections', 'sections.id', '=', 'student_section.section_id')
            ->where('sections.school_class_id', $class->id)
            ->where('student_section.academic_year_id', $year->id)
            ->pluck('student_section.student_id');

        $created = 0;
        $skipped = 0;
        $numbers = [];

        foreach ($studentIds as $studentId) {
            $existing = Invoice::where([
                'student_id'       => $studentId,
                'academic_year_id' => $year->id,
                'term'             => $term,
            ])->exists();

            if ($existing) {
                $skipped++;
                continue;
            }

            $student = Student::with('scholarships')->find($studentId);
            $scholarship = $student->scholarships()->where('is_active', true)->first();

            DB::transaction(function () use ($student, $year, $term, $fees, $scholarship, $dueDate, &$created, &$numbers) {
                $subtotal = '0.00';
                foreach ($fees as $fee) {
                    $subtotal = bcadd($subtotal, (string) $fee->amount, 2);
                }

                // Apply scholarship discount
                $discount = '0.00';
                if ($scholarship) {
                    if ($scholarship->type === 'percent') {
                        $discount = bcdiv(bcmul($subtotal, (string) $scholarship->value, 4), '100', 2);
                    } else {
                        $discount = min((string) $scholarship->value, $subtotal);
                    }
                }

                $total = bcsub($subtotal, $discount, 2);
                $number = $this->nextNumber();

                $invoice = Invoice::create([
                    'number'           => $number,
                    'student_id'       => $student->id,
                    'academic_year_id' => $year->id,
                    'term'             => $term,
                    'subtotal'         => $subtotal,
                    'discount'         => $discount,
                    'total'            => $total,
                    'paid'             => '0.00',
                    'status'           => 'unpaid',
                    'due_date'         => $dueDate,
                ]);

                foreach ($fees as $fee) {
                    InvoiceItem::create([
                        'invoice_id'       => $invoice->id,
                        'fee_structure_id' => $fee->id,
                        'description'      => $fee->name,
                        'amount'           => $fee->amount,
                    ]);
                }

                if ($discount !== '0.00') {
                    InvoiceItem::create([
                        'invoice_id'  => $invoice->id,
                        'description' => 'Scholarship / Discount',
                        'amount'      => bcmul('-1', $discount, 2),
                    ]);
                }

                $numbers[] = $number;
            });

            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped, 'invoice_numbers' => $numbers];
    }

    /**
     * Allocate a new sequential invoice number inside the caller's transaction.
     * Uses SELECT FOR UPDATE to be race-safe.
     *
     * Sequences are PER BRANCH; the branch code is embedded in the number
     * (INV-MC-2026-00001) so numbers stay globally unique across branches.
     * With no branch context (console, legacy single-branch) the pre-M1
     * format INV-2026-00001 is kept.
     */
    public function nextNumber(): string
    {
        $year     = (int) now()->format('Y');
        $branchId = \App\Support\BranchContext::current();

        // NB: SQL `branch_id = NULL` never matches — the no-context (legacy)
        // sequence row needs whereNull explicitly.
        $acquire = fn () => InvoiceSequence::lockForUpdate()
            ->where('year', $year)
            ->when(
                $branchId === null,
                fn ($q) => $q->whereNull('branch_id'),
                fn ($q) => $q->where('branch_id', $branchId)
            )
            ->first();

        $seq = $acquire();

        if (!$seq) {
            InvoiceSequence::create(['year' => $year, 'branch_id' => $branchId, 'last_number' => 0]);
            // Re-acquire with lock in case of race
            $seq = $acquire();
        }

        $seq->increment('last_number');
        $seq->refresh();

        $branchCode = $branchId ? \App\Models\Branch::find($branchId)?->code : null;
        $middle     = $branchCode ? "{$branchCode}-{$year}" : (string) $year;

        return 'INV-' . $middle . '-' . str_pad($seq->last_number, 5, '0', STR_PAD_LEFT);
    }
}
