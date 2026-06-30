<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    /**
     * Record a payment against an invoice.
     * Rejects overpayments and payments on already-paid invoices.
     *
     * @throws ValidationException
     */
    public function record(Invoice $invoice, float|string $amount, string $method, ?string $reference, User $receivedBy): Payment
    {
        $amount = (string) $amount;

        return DB::transaction(function () use ($invoice, $amount, $method, $reference, $receivedBy) {
            // Re-read inside transaction to get current state
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);

            if ($invoice->isPaid()) {
                throw ValidationException::withMessages([
                    'amount' => __('payment.already_paid'),
                ]);
            }

            $remaining = $invoice->remainingBalance();

            if (bccomp($amount, '0.00', 2) <= 0) {
                throw ValidationException::withMessages([
                    'amount' => __('payment.must_be_positive'),
                ]);
            }

            if (bccomp($amount, $remaining, 2) > 0) {
                throw ValidationException::withMessages([
                    'amount' => __('payment.overpayment', ['max' => number_format((float) $remaining, 2)]),
                ]);
            }

            $payment = Payment::create([
                'invoice_id'  => $invoice->id,
                'amount'      => $amount,
                'method'      => $method,
                'reference'   => $reference,
                'received_by' => $receivedBy->id,
                'paid_at'     => now(),
            ]);

            $newPaid = bcadd((string) $invoice->paid, $amount, 2);
            $newRemaining = bcsub((string) $invoice->total, $newPaid, 2);

            $status = match (true) {
                bccomp($newRemaining, '0.00', 2) <= 0 => 'paid',
                bccomp($newPaid, '0.00', 2) > 0        => 'partial',
                default                                 => 'unpaid',
            };

            $invoice->update(['paid' => $newPaid, 'status' => $status]);

            return $payment;
        });
    }
}
