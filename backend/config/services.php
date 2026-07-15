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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],
    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        'mock_booking_enabled' => env('APPOINTMENT_MOCK_PAYMENT_ENABLED', false),
    ],

    'google_sheets' => [
        'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID'),
        'range' => env('GOOGLE_SHEETS_RANGE', 'A:L'),
        'service_account_json_path' => env('GOOGLE_SHEETS_SERVICE_ACCOUNT_JSON_PATH'),
        'service_account_json' => env('GOOGLE_SHEETS_SERVICE_ACCOUNT_JSON'),
        'timeout' => env('GOOGLE_SHEETS_TIMEOUT', 30),
        'sync_schedule_enabled' => env('GOOGLE_SHEETS_EXTERNAL_BOOKINGS_SYNC_ENABLED', false),
        'sync_schedule' => env('GOOGLE_SHEETS_EXTERNAL_BOOKINGS_SYNC_SCHEDULE', '*/15 * * * *'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Whereby Video Consultation Service
    |--------------------------------------------------------------------------
    |
    | Configuration for Whereby video consultation service.
    | Get your API key from https://whereby.com/information/embedded/
    |
    */
    'whereby' => [
        'api_key' => env('WHEREBY_API_KEY', env('WHEREBY_SECRET', '')),
        'base_url' => env('WHEREBY_BASE_URL', 'https://api.whereby.dev/v1'),
        'webhook_secret' => env('WHEREBY_WEBHOOK_SECRET', 'd7c5t2ncbde95wgx7ehanwbjr23fx0ta'),
    ],

];