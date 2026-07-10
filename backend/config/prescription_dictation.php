<?php

$supportedLocales = array_values(array_filter(array_map(
    static fn ($locale) => trim((string) $locale),
    explode(',', (string) env('PRESCRIPTION_DICTATION_SUPPORTED_LOCALES', 'auto,en-IN,hi-IN,pa-IN'))
)));

return [
    'enabled' => (bool) env('PRESCRIPTION_DICTATION_ENABLED', false),
    'input_mode' => env('PRESCRIPTION_DICTATION_INPUT_MODE', 'off'),
    'text_mode_max_chars' => (int) env('PRESCRIPTION_DICTATION_TEXT_MAX_CHARS', 1000),
    'speech_locale' => env('PRESCRIPTION_DICTATION_SPEECH_LOCALE', 'en-IN'),
    'supported_locales' => $supportedLocales ?: ['auto', 'en-IN', 'en-US', 'hi-IN', 'pa-IN'],
    'allow_custom_locale' => (bool) env('PRESCRIPTION_DICTATION_ALLOW_CUSTOM_LOCALE', true),
    'doctor_review_required' => true,
    'single_medicine_only' => true,
];
