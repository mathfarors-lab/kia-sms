<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS Driver
    |--------------------------------------------------------------------------
    | Supported: "log", "plasgate", "twilio"
    |
    | "log" (default) writes messages to the daily log — safe for local/dev/CI.
    | "plasgate" — Cambodian gateway (cloudapi.plasgate.com), local rates.
    | "twilio" — international; requires composer require twilio/sdk.
    */
    'driver' => env('SMS_DRIVER', 'log'),

    'plasgate' => [
        'base_url'    => env('PLASGATE_BASE_URL', 'https://cloudapi.plasgate.com/rest/send'),
        'private_key' => env('PLASGATE_PRIVATE_KEY'),
        'secret'      => env('PLASGATE_SECRET'),
        'sender'      => env('PLASGATE_SENDER', 'KIA School'),
    ],

    'twilio' => [
        'sid'   => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from'  => env('TWILIO_FROM'),
    ],
];
