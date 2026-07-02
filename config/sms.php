<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS Driver
    |--------------------------------------------------------------------------
    | Supported: "log", "twilio"
    | Set SMS_DRIVER=twilio in production .env and supply credentials below.
    */
    'driver' => env('SMS_DRIVER', 'log'),

    'twilio' => [
        'sid'   => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from'  => env('TWILIO_FROM'),
    ],
];
