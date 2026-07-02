<?php

namespace App\Services;

use App\Models\BakongCallback;
use App\Models\BakongFailedVerification;
use App\Models\Invoice;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BakongWebhookService
{
    /**
     * Process a Bakong webhook callback (entry point from the controller).
     *
     * Returns a BakongCallback for verified+recorded calls, or null for
     * failed-verification attempts (which are written to the audit table instead).
     * The controller always returns 200 — we never leak rejection reasons to callers.
     */
    public function handle(array $payload): ?BakongCallback
    {
        $ref = $this->extractRef($payload);

        if (!$ref) {
            Log::warning('Bakong: missing transaction_reference in callback payload');
            abort(422, 'Missing transaction reference');
        }

        [$sigValid, $reason] = $this->verifySignature($payload);

        if (!$sigValid) {
            // Write to the audit table — NOT to bakong_callbacks.
            // This prevents an attacker from occupying the UNIQUE(transaction_reference)
            // slot on bakong_callbacks, which would cause the real callback to be
            // silently treated as a duplicate and never apply the payment.
            $this->recordFailedVerification($ref, $reason, $payload);
            return null;
        }

        return $this->applyVerifiedCallback($ref, $payload);
    }

    /**
     * Apply a pre-verified callback (used by handle() and the admin replay action).
     * Idempotent: calling twice with the same ref applies the payment at most once.
     *
     * The insert into bakong_callbacks is the concurrency gate:
     * - Only the process that wins the INSERT calls applyPayment().
     * - Any concurrent process hits UniqueConstraintViolationException and returns
     *   the existing row WITHOUT calling applyPayment() — no double-pay.
     */
    public function applyVerifiedCallback(string $ref, array $payload): ?BakongCallback
    {
        // Fast path: already processed (read before taking a write lock).
        $existing = BakongCallback::where('transaction_reference', $ref)->first();
        if ($existing) {
            return $existing; // wasRecentlyCreated = false
        }

        return DB::transaction(function () use ($payload, $ref) {
            try {
                $callback = BakongCallback::create([
                    'transaction_reference' => $ref,
                    'amount'                => $payload['amount'] ?? '0',
                    'currency'              => $payload['currency'] ?? 'USD',
                    'status'                => $payload['status'] ?? 'confirmed',
                    'payer_account'         => $payload['payerAccount'] ?? null,
                    'raw_payload'           => $this->sanitizePayload($payload),
                    'signature_valid'       => true,
                ]);
            } catch (UniqueConstraintViolationException) {
                // Concurrent request won the INSERT race — return winner, no double-apply.
                return BakongCallback::where('transaction_reference', $ref)->firstOrFail();
            }

            if (($payload['status'] ?? '') === 'confirmed') {
                $this->applyPayment($callback, $payload);
            }

            return $callback;
        });
    }

    // -------------------------------------------------------------------------
    // Signature verification (public so the replay action can call it directly)
    // -------------------------------------------------------------------------

    /**
     * Returns [bool $valid, string $reason].
     * Reasons: 'secret-unset' | 'missing-header' | 'bad-sig'
     *
     * ⚠️  IMPORTANT: The header name (BAKONG_SIGNATURE_HEADER) and algorithm
     * (BAKONG_SIGNATURE_ALGO) MUST be confirmed against the provider's merchant
     * documentation before go-live. This code can only verify internal consistency.
     */
    public function verifySignature(array $payload): array
    {
        $secret = config('services.bakong.webhook_secret');

        if (!$secret) {
            Log::warning('Bakong: BAKONG_WEBHOOK_SECRET is not set — all callbacks rejected (fails closed).');
            return [false, 'secret-unset'];
        }

        $receivedSig = $payload['signature'] ?? '';
        if ($receivedSig === '') {
            return [false, 'missing-header'];
        }

        $algo        = config('services.bakong.signature_algo', 'sha256');
        $rawBody     = $payload['rawBody'] ?? '';
        $expectedSig = hash_hmac($algo, $rawBody, $secret);

        if (!hash_equals($expectedSig, $receivedSig)) {
            return [false, 'bad-sig'];
        }

        return [true, ''];
    }

    /** Extract the provider-agnostic transaction reference from a payload. */
    public function extractRef(array $payload): ?string
    {
        return $payload['transaction_id'] ?? $payload['transactionId'] ?? null;
    }

    // -------------------------------------------------------------------------
    // Audit logging for failed verifications
    // -------------------------------------------------------------------------

    private function recordFailedVerification(string $ref, string $reason, array $payload): void
    {
        BakongFailedVerification::create([
            'transaction_reference' => $ref,
            'reason'                => $reason,
            'raw_payload'           => $this->sanitizePayload($payload),
            // Stored so admin replay can reconstruct the exact bytes for re-verification.
            'raw_body'              => $payload['rawBody'] ?? null,
            'received_signature'    => $payload['signature'] ?? null,
        ]);

        Log::warning("Bakong: failed verification [{$reason}]", [
            'transaction_reference' => $ref,
            'reason'                => $reason,
        ]);
    }

    /**
     * Strip internal-only envelope fields before persisting.
     * raw_body is stored in its own column; signature in received_signature.
     */
    private function sanitizePayload(array $payload): array
    {
        return array_diff_key($payload, array_flip(['rawBody', 'signature']));
    }

    // -------------------------------------------------------------------------
    // Payment application — valid signature is necessary but not sufficient
    // -------------------------------------------------------------------------

    private function applyPayment(BakongCallback $callback, array $payload): void
    {
        // --- Currency check ---
        $callbackCurrency = strtoupper($payload['currency'] ?? 'USD');
        if ($callbackCurrency !== 'USD') {
            $this->flagCallback($callback, 'currency-mismatch');
            return;
        }

        // --- Invoice lookup ---
        $invoiceRef = $payload['merchantRef'] ?? $payload['merchant_ref'] ?? null;
        if (!$invoiceRef) {
            $this->flagCallback($callback, 'unmatched-ref');
            return;
        }

        $invoice = Invoice::where('number', $invoiceRef)
            ->where('status', '!=', 'paid')
            ->lockForUpdate()
            ->first();

        if (!$invoice) {
            // Verified but no matching unpaid invoice — flag for admin review.
            $this->flagCallback($callback, 'unmatched-ref');
            Log::warning('Bakong: callback references unknown or already-paid invoice', [
                'ref'         => $callback->transaction_reference,
                'invoiceRef'  => $invoiceRef,
            ]);
            return;
        }

        // --- Amount check (overpayment guard) ---
        $alreadyPaid = number_format((float) $invoice->payments()->sum('amount'), 2, '.', '');
        $outstanding = bcsub((string) $invoice->total, $alreadyPaid, 2);

        if (bccomp((string) $callback->amount, $outstanding, 2) > 0) {
            $this->flagCallback($callback, 'amount-mismatch');
            Log::warning('Bakong: callback amount exceeds outstanding balance — flagged for admin review', [
                'ref'         => $callback->transaction_reference,
                'amount'      => $callback->amount,
                'outstanding' => $outstanding,
            ]);
            return;
        }

        // --- Safe to apply ---
        $callback->update(['invoice_id' => $invoice->id]);

        $invoice->payments()->create([
            'amount'    => $callback->amount,
            'method'    => 'khqr',
            'reference' => $callback->transaction_reference,
        ]);

        $totalPaid = $invoice->payments()->sum('amount');
        if (bccomp((string) $totalPaid, (string) $invoice->total, 2) >= 0) {
            $invoice->update(['status' => 'paid']);
        }
    }

    private function flagCallback(BakongCallback $callback, string $reason): void
    {
        $callback->update(['flag_reason' => $reason]);
        Log::warning("Bakong: verified callback flagged [{$reason}] — payment NOT applied, admin review required", [
            'transaction_reference' => $callback->transaction_reference,
            'reason'                => $reason,
        ]);
    }
}
