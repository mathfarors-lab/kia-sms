<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $to, string $message): bool
    {
        $driver = config('sms.driver', 'log');

        return match ($driver) {
            'plasgate' => $this->sendViaPlasgate($to, $message),
            'twilio'   => $this->sendViaTwilio($to, $message),
            default    => $this->sendViaLog($to, $message),
        };
    }

    private function sendViaLog(string $to, string $message): bool
    {
        Log::channel('daily')->info('[SMS]', ['to' => $to, 'message' => $message]);
        return true;
    }

    /**
     * Plasgate (cloudapi.plasgate.com) — Cambodian SMS gateway.
     * Auth: private_key as query param + X-Secret header.
     */
    private function sendViaPlasgate(string $to, string $message): bool
    {
        $privateKey = config('sms.plasgate.private_key');
        $secret     = config('sms.plasgate.secret');

        if (!$privateKey || !$secret) {
            Log::error('Plasgate SMS not configured — falling back to log', ['to' => $to]);
            return $this->sendViaLog($to, $message);
        }

        try {
            $response = Http::withHeaders(['X-Secret' => $secret])
                ->timeout(10)
                ->post(config('sms.plasgate.base_url') . '?private_key=' . urlencode($privateKey), [
                    'sender'  => config('sms.plasgate.sender'),
                    'to'      => $this->normalizeKhmerNumber($to),
                    'content' => $message,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('Plasgate SMS failed', ['to' => $to, 'status' => $response->status(), 'body' => $response->body()]);
            return false;
        } catch (\Exception $e) {
            Log::error('Plasgate SMS failed: ' . $e->getMessage(), ['to' => $to]);
            return false;
        }
    }

    private function sendViaTwilio(string $to, string $message): bool
    {
        $sid   = config('sms.twilio.sid');
        $token = config('sms.twilio.token');
        $from  = config('sms.twilio.from');

        if (!$sid || !$token || !$from) {
            Log::error('Twilio SMS not configured — falling back to log', ['to' => $to]);
            return $this->sendViaLog($to, $message);
        }

        if (!class_exists(\Twilio\Rest\Client::class)) {
            Log::error('Twilio SDK not installed (composer require twilio/sdk) — falling back to log', ['to' => $to]);
            return $this->sendViaLog($to, $message);
        }

        try {
            $client = new \Twilio\Rest\Client($sid, $token);
            $client->messages->create($to, ['from' => $from, 'body' => $message]);
            return true;
        } catch (\Exception $e) {
            Log::error('Twilio SMS failed: ' . $e->getMessage(), ['to' => $to]);
            return false;
        }
    }

    /**
     * Normalize Cambodian numbers to international format without "+":
     * "012 345 678" / "012345678" → "85512345678"; leaves "855…" as-is.
     */
    private function normalizeKhmerNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number);

        if (str_starts_with($digits, '855')) {
            return $digits;
        }
        if (str_starts_with($digits, '0')) {
            return '855' . substr($digits, 1);
        }
        return $digits;
    }
}
