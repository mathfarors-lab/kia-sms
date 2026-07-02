<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PaymentIntent;

/**
 * Generates dynamic KHQR codes and manages PaymentIntent lifecycle.
 *
 * In fake/dev mode (BAKONG_FAKE_MODE=true or no base URL):
 *   - Generates a deterministic placeholder QR string (NOT scannable by real Bakong wallets).
 *   - Safe for local development and CI.
 *
 * In production:
 *   - Replace generateQrString() with the official NBC KHQR SDK call before go-live.
 *   - The MD5 of whatever string you produce here is the polling key.
 *
 * ⚠️ Production calls to the Bakong Open API MUST originate from a Cambodia-based server.
 *    Calls from outside Cambodia are blocked by NBC.
 */
class KhqrService
{
    public function __construct(private BakongTokenService $tokenService) {}

    /**
     * Return an existing pending (non-expired) PaymentIntent for this invoice,
     * or create a fresh one with a new QR code.
     */
    public function getOrCreateIntent(Invoice $invoice): PaymentIntent
    {
        $existing = PaymentIntent::where('invoice_id', $invoice->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            return $existing;
        }

        $amount    = number_format((float) $invoice->remainingBalance(), 2, '.', '');
        $currency  = 'USD';
        $billRef   = $invoice->number;
        $qrString  = $this->generateQrString($amount, $currency, $billRef);
        $md5       = md5($qrString);
        $expiresAt = now()->addMinutes(config('services.bakong.qr_ttl_minutes', 10));

        return PaymentIntent::create([
            'invoice_id'  => $invoice->id,
            'qr_string'   => $qrString,
            'md5_hash'    => $md5,
            'bill_number' => $billRef,
            'amount'      => $amount,
            'currency'    => $currency,
            'expires_at'  => $expiresAt,
            'status'      => 'pending',
        ]);
    }

    /**
     * Mark all expired pending intents as expired (called by the polling command
     * before each poll cycle so expired intents are never submitted to the API).
     */
    public function expireStaleIntents(): int
    {
        return PaymentIntent::where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);
    }

    /**
     * Generate the KHQR QR string.
     *
     * PRODUCTION NOTE: Replace this method body with a call to the official NBC
     * KHQR SDK (bakong/khqr-sdk). The MD5 of the string produced here must match
     * exactly what is polled via check_transaction_by_md5.
     */
    private function generateQrString(string $amount, string $currency, string $billRef): string
    {
        if ($this->isFakeMode()) {
            // Dev/CI placeholder — NOT scannable by a real Bakong wallet.
            // Unique per bill+amount+second so each intent gets a distinct MD5.
            return sprintf('KHQR-FAKE|%s|%s|%s|%d', $billRef, $amount, $currency, now()->timestamp);
        }

        // Production: structural EMV-lite KHQR string.
        // ⚠️  Use the official NBC SDK for a fully compliant KHQR that real wallets accept.
        $merchantId   = config('services.bakong.merchant_id', '');
        $merchantName = config('services.bakong.merchant_name', 'KIA School');
        $city         = config('services.bakong.merchant_city', 'Phnom Penh');

        return sprintf(
            '00020101021230%02d0016net.bakong%04d%s52045999530384054%s5802KH59%02d%s60%02d%s62%02d%s',
            16 + strlen($merchantId),
            strlen($merchantId),
            $merchantId,
            $amount,
            strlen($merchantName),
            $merchantName,
            strlen($city),
            $city,
            strlen($billRef),
            $billRef
        );
    }

    private function isFakeMode(): bool
    {
        return config('services.bakong.fake_mode', false) || !config('services.bakong.base_url');
    }
}
