<?php

return [
    'enabled' => (bool) env('WHATSAPP_NOTIFIER_ENABLED', true),

    'api_version' => env('WHATSAPP_API_VERSION', 'v23.0'),
    'base_url' => env('WHATSAPP_GRAPH_BASE_URL', 'https://graph.facebook.com'),
    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),

    'default_country_code' => env('WHATSAPP_DEFAULT_COUNTRY_CODE', '91'),

    'webhook' => [
        'enabled' => (bool) env('WHATSAPP_WEBHOOK_ENABLED', true),
        'path' => env('WHATSAPP_WEBHOOK_PATH', 'api/v2/webhooks/whatsapp'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'middleware' => ['api'],
    ],

    'log' => [
        'enabled' => (bool) env('WHATSAPP_NOTIFIER_LOG_ENABLED', true),
        'keep_payload' => (bool) env('WHATSAPP_NOTIFIER_LOG_PAYLOAD', true),
    ],
];
