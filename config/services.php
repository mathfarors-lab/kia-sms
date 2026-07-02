<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'bakong' => [
        // ── Pull/polling model (default) ──────────────────────────────────────
        // Set BAKONG_USE_POLLING=true and configure the fields below.
        // See routes/console.php for the scheduled check command.
        'use_polling'     => env('BAKONG_USE_POLLING', true),

        // Bakong Open API base URL (obtained from NBC merchant portal).
        // Leave blank to enable fake/dev mode — no live API calls will be made.
        'base_url'        => env('BAKONG_API_BASE_URL'),

        // Registered merchant email for POST /v1/auth/login
        'email'           => env('BAKONG_MERCHANT_EMAIL'),

        // Merchant wallet ID for KHQR generation (e.g. 855012345678@aba)
        'merchant_id'     => env('BAKONG_MERCHANT_ID'),
        'merchant_name'   => env('BAKONG_MERCHANT_NAME', 'KIA School'),
        'merchant_city'   => env('BAKONG_MERCHANT_CITY', 'Phnom Penh'),

        // KHQR window — how long the QR is valid (minutes). Default: 10.
        // Bakong standard is ~10 min; adjust only if NBC grants a longer window.
        'qr_ttl_minutes'  => env('BAKONG_QR_TTL_MINUTES', 10),

        // Set to true in local/dev/CI to skip all live API calls and use
        // fake placeholder QR strings and fake tokens.
        'fake_mode'       => env('BAKONG_FAKE_MODE', true),

        // ── Push/webhook model (bank/PSP integration) ─────────────────────────
        // Only enable if your acquiring bank provides a real PUSH webhook.
        // Do NOT run both polling and an open webhook as live paths simultaneously.
        // Set BAKONG_DISABLE_WEBHOOK=false to re-enable (off by default when polling).
        'disable_webhook' => env('BAKONG_DISABLE_WEBHOOK', true),

        // HMAC settings for inbound push webhook (if disable_webhook=false)
        'webhook_secret'  => env('BAKONG_WEBHOOK_SECRET'),
        'signature_header'=> env('BAKONG_SIGNATURE_HEADER', 'X-Bakong-Signature'),
        'signature_algo'  => env('BAKONG_SIGNATURE_ALGO',   'sha256'),
    ],

];
