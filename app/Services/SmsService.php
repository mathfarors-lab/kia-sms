<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $to, string $message): bool
    {
        $driver = config('sms.driver', 'log');

        return match ($driver) {
            'twilio' => $this->sendViaTwilio($to, $message),
            default  => $this->sendViaLog($to, $message),
        };
    }

    private function sendViaLog(string $to, string $message): bool
    {
        Log::channel('daily')->info('[SMS]', ['to' => $to, 'message' => $message]);
        return true;
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

        try {
            $client = new \Twilio\Rest\Client($sid, $token);
            $client->messages->create($to, ['from' => $from, 'body' => $message]);
            return true;
        } catch (\Exception $e) {
            Log::error('Twilio SMS failed: ' . $e->getMessage(), ['to' => $to]);
            return false;
        }
    }
}
