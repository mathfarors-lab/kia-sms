<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class BillingStatementController extends Controller
{
    /** HTML preview — testable and browseable. */
    public function show(Student $student)
    {
        $this->authorizeAccess($student);

        $data = $this->buildStatement($student);

        return view('billing-statement.show', array_merge(['student' => $student], $data));
    }

    /** PDF download. */
    public function pdf(Student $student)
    {
        $this->authorizeAccess($student);

        $data = $this->buildStatement($student);

        $pdf = Pdf::loadView('pdf.billing-statement', array_merge(['student' => $student], $data))
            ->setPaper('a4')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        return $pdf->download("billing-statement-{$student->student_code}.pdf");
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────

    /**
     * Every invoice charge and every payment against it, interleaved
     * chronologically into one ledger with a running bcmath balance.
     */
    private function buildStatement(Student $student): array
    {
        $invoices = Invoice::where('student_id', $student->id)
            ->with(['academicYear', 'payments' => fn ($q) => $q->orderBy('paid_at')])
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        $entries = collect();

        foreach ($invoices as $invoice) {
            $entries->push([
                'date'        => $invoice->due_date ?? $invoice->created_at,
                'type'        => 'charge',
                'description' => trim($invoice->number . ' — ' . ($invoice->academicYear->name ?? '') . ' ' . $invoice->term),
                'amount'      => (string) $invoice->total,
                'invoice'     => $invoice,
            ]);

            foreach ($invoice->payments as $payment) {
                $entries->push([
                    'date'        => $payment->paid_at,
                    'type'        => 'payment',
                    'description' => $invoice->number . ' — ' . ucfirst($payment->method),
                    'amount'      => (string) $payment->amount,
                    'invoice'     => $invoice,
                    'payment'     => $payment,
                ]);
            }
        }

        $entries = $entries->sortBy('date')->values();

        $balance      = '0.00';
        $totalCharged = '0.00';
        $totalPaid    = '0.00';

        $ledger = $entries->map(function ($row) use (&$balance, &$totalCharged, &$totalPaid) {
            if ($row['type'] === 'charge') {
                $balance      = bcadd($balance, $row['amount'], 2);
                $totalCharged = bcadd($totalCharged, $row['amount'], 2);
            } else {
                $balance   = bcsub($balance, $row['amount'], 2);
                $totalPaid = bcadd($totalPaid, $row['amount'], 2);
            }
            $row['running_balance'] = $balance;
            return $row;
        });

        return [
            'ledger'       => $ledger,
            'totalCharged' => $totalCharged,
            'totalPaid'    => $totalPaid,
            'balance'      => $balance,
        ];
    }

    private function authorizeAccess(Student $student): void
    {
        $user = Auth::user();

        if ($user->hasRole(['admin', 'accountant', 'principal'])) {
            return;
        }

        if ($user->hasRole('student')) {
            abort_unless($user->student?->id === $student->id, 403);
            return;
        }

        if ($user->hasRole('parent')) {
            abort_unless($user->wards()->where('students.id', $student->id)->exists(), 403);
            return;
        }

        abort(403);
    }
}
