<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Deepgram Speech-to-Text
    |--------------------------------------------------------------------------
    | API key is read from DEEPGRAM_API_KEY env.
    | Admin can manage these via the admin settings panel.
    */

    'enabled'               => env('DEEPGRAM_ENABLED', false),
    'api_key'               => env('DEEPGRAM_API_KEY', ''),
    'model'                 => env('DEEPGRAM_MODEL', 'nova-2'),
    'language'              => env('DEEPGRAM_LANGUAGE', 'en'),
    'monthly_budget_minutes' => env('DEEPGRAM_MONTHLY_BUDGET_MINUTES', 0),
];
