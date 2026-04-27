<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure all the variables related to the OTP authentication
    | system. This controls the length, expiry, and resend limits for the OTP.
    |
    */

    'enabled' => env('OTP_ENABLED', true),

    'length' => env('OTP_LENGTH', 6),

    // Can be 'minutes' or 'seconds'
    'expiry_unit' => env('OTP_EXPIRY_UNIT', 'seconds'),

    'expiry_seconds' => env('OTP_EXPIRY_SECONDS', 60),

    'resend_seconds' => env('OTP_RESEND_SECONDS', 60),
];
