<?php

namespace App\Services;

use App\Models\PaymentIntent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Polls the Bakong Open API for confirmed transactions.
 *
 * Calls POST /v1/merchant/check-transaction-by-md5 with the Bearer token
 * from BakongTokenService and up to 50 MD5s per request.
 *
 * On responseCode == 0 with data, the confirmed transaction is applied
 * via BakongWebhookService::applyVerifiedCallback() — same idempotent,
 * validated, flag-on-mismatch path used for inbound webhook callbacks.
 *
 * ⚠️  CAMBODIA-SERVER REQUIREMENT: Calls to the check endpoint MUST originate
 *     from a Cambodia-based IP. NBC blocks requests from outside Cambodia.
 *     Deploy the server (or the worker running this job) inside Cambodia.
 */
class BakongPollingService
{
    public function __construct(
        private BakongTokenService   $tokenService,
        private BakongWebhookService $webhookService,
    ) {}

    /**
     * Poll a collection of pending PaymentIntents (max 50 — caller must chunk).
     */
    public function checkChunk(Collection $intents): void
    {
        if (config('services.bakong.fake_mode', false) || !config('services.bakong.base_url')) {
            Log::info("Bakong polling: fake mode — skipping live API call for {$intents->count()} intent(s)");
            return;
        }

        $token   = $this->tokenService->getToken();
        $md5s    = $intents->pluck('md5_hash')->all();
        $baseUrl = config('services.bakong.base_url');

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->post("{$baseUrl}/v1/merchant/check-transaction-by-md5", ['md5' => $md5s]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Bakong polling: connection error', ['error' => $e->getMessage()]);
            return;
        }

        // Log every request for reconciliation audit trail.
        Log::info('Bakong poll request', [
            'request_id'   => $response->header('X-Request-ID', 'n/a'),
            'responseCode' => $response->json('responseCode'),
            'errorCode'    => $response->json('errorCode'),
            'md5_count'    => count($md5s),
            'http_status'  => $response->status(),
        ]);

        if ($response->status() === 401) {
            Log::warning('Bakong polling: 401 Unauthorized — forcing token renewal for next cycle');
            $this->tokenService->forceRenew();
            return;
        }

        if ($response->json('responseCode') !== 0) {
            Log::warning('Bakong polling: non-zero responseCode', [
                'responseCode' => $response->json('responseCode'),
                'errorCode'    => $response->json('errorCode'),
            ]);
            return;
        }

        // data may be a single object or an array of objects depending on how many
        // MD5s were matched. Normalise to an array.
        $data = $response->json('data') ?? [];
        if (!empty($data) && !isset($data[0])) {
            $data = [$data]; // single-object response
        }

        foreach ($data as $txn) {
            $md5    = $txn['md5'] ?? null;
            $intent = $md5 ? $intents->firstWhere('md5_hash', $md5) : null;

            if (!$intent) {
                Log::warning('Bakong polling: confirmed txn md5 not matched in polled set', ['md5' => $md5]);
                continue;
            }

            $this->applyTransaction($intent, $txn);
        }

        // Record poll time on all intents (even unconfirmed ones) for audit.
        PaymentIntent::whereIn('id', $intents->pluck('id'))->update(['polled_at' => now()]);
    }

    private function applyTransaction(PaymentIntent $intent, array $txn): void
    {
        $hash = $txn['hash'] ?? null;
        if (!$hash) {
            Log::warning('Bakong polling: confirmed transaction missing hash field', [
                'txn' => array_intersect_key($txn, array_flip(['md5', 'amount', 'currency'])),
            ]);
            return;
        }

        // Construct the canonical payload for the apply-payment path.
        // This reuses the exact same validation + idempotency logic as the
        // inbound webhook path — currency check, amount check, flag-on-mismatch.
        $payload = [
            'transaction_id' => $hash,
            'amount'         => number_format((float) ($txn['amount'] ?? $intent->amount), 2, '.', ''),
            'currency'       => strtoupper($txn['currency'] ?? $intent->currency),
            'status'         => 'confirmed',
            'payerAccount'   => $txn['fromAccountId'] ?? null,
            'merchantRef'    => $intent->bill_number,
        ];

        $callback = $this->webhookService->applyVerifiedCallback($hash, $payload);

        if (!$callback) {
            return;
        }

        if ($callback->flag_reason) {
            $intent->update([
                'status'       => 'flagged',
                'error_reason' => $callback->flag_reason,
                'bakong_hash'  => $hash,
            ]);
        } else {
            $intent->update(['status' => 'paid', 'bakong_hash' => $hash]);
        }
    }
}
