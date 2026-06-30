<?php

namespace App\Services;

/**
 * Generates a KHQR / Bakong EMVCo QR payload string.
 *
 * Spec: https://bakong.nbc.gov.kh/download/KHQR/sdk/KHQR_SDK_Documentation.pdf
 * This produces a valid EMVCo QR string.  Real settlement requires the Bakong
 * webhook / callback API to confirm — see TODO below.
 */
class KhqrService
{
    // TODO(Phase 5): Replace with real Bakong merchant credentials loaded from settings.
    private const BAKONG_GUID  = '00A6012601081.0.0';
    private const MERCHANT_ID  = 'kia_school@wing';   // placeholder — set via settings
    private const MERCHANT_NAME = 'KIA School';
    private const MERCHANT_CITY = 'Phnom Penh';
    private const COUNTRY       = 'KH';
    private const CURRENCY_KHR  = '116';
    private const CURRENCY_USD  = '840';

    /**
     * Build the EMVCo QR payload for an invoice amount (USD).
     *
     * TODO(Bakong webhook): After the parent scans and pays, Bakong will POST to
     * /api/bakong/callback with a transaction reference. That endpoint should
     * call PaymentService::record() to auto-confirm the payment.
     */
    public function generate(string $amount, string $currency = 'USD', ?string $invoiceRef = null): string
    {
        $currencyCode = $currency === 'KHR' ? self::CURRENCY_KHR : self::CURRENCY_USD;

        // Tag 29 — Bakong merchant account info
        $merchantAccount = $this->tlv('00', self::BAKONG_GUID)
            . $this->tlv('01', self::MERCHANT_ID);
        if ($invoiceRef) {
            $merchantAccount .= $this->tlv('05', $invoiceRef);
        }

        $payload = $this->tlv('00', '01')             // Payload Format Indicator
            . $this->tlv('01', '12')                   // Dynamic QR
            . $this->tlv('29', $merchantAccount)       // Bakong merchant account
            . $this->tlv('52', '8299')                 // MCC — Education
            . $this->tlv('53', $currencyCode)          // Currency
            . $this->tlv('54', $amount)                // Amount
            . $this->tlv('58', self::COUNTRY)          // Country
            . $this->tlv('59', self::MERCHANT_NAME)    // Merchant name
            . $this->tlv('60', self::MERCHANT_CITY);   // City

        // Tag 63 placeholder (CRC appended with value "0000" before computing)
        $payload .= '6304';
        $payload .= $this->crc16($payload);

        return $payload;
    }

    private function tlv(string $tag, string $value): string
    {
        return $tag . str_pad(strlen($value), 2, '0', STR_PAD_LEFT) . $value;
    }

    private function crc16(string $str): string
    {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($str); $i++) {
            $crc ^= ord($str[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
                $crc &= 0xFFFF;
            }
        }
        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
