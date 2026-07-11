<?php

namespace App\Notifications\Channels;

use App\Services\SmsService;
use Illuminate\Notifications\Notification;

/**
 * Custom notification channel: routes a notification's toSms() text through
 * SmsService (log/plasgate/twilio per SMS_DRIVER). Silently skips notifiables
 * with no phone number and notifications with no/empty toSms().
 */
class SmsChannel
{
    public function __construct(private SmsService $sms) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $phone = $notifiable->routeNotificationFor('sms', $notification) ?? $notifiable->phone ?? null;
        if (!$phone) {
            return;
        }

        $message = $notification->toSms($notifiable);
        if (!$message) {
            return;
        }

        $this->sms->send($phone, $message);
    }
}
