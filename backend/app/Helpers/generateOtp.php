<?php

use App\Services\SettingService;

if (!function_exists('generateOtp')) {
    /**
     * Generate a numeric OTP based on settings
     */
    function generateOtp(): string
    {
        try {
            $length = SettingService::getOtpLength();
        } catch (\Exception $e) {
            $length = 6; // Default fallback
        }

        $max = (int) str_repeat('9', $length);
        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('getOtpExpiryMinutes')) {
    /**
     * Get OTP expiry time in minutes
     */
    function getOtpExpiryMinutes(): int
    {
        try {
            return SettingService::getOtpExpiryMinutes();
        } catch (\Exception $e) {
            return 2; // Default fallback
        }
    }
}

if (!function_exists('getOtpResendSeconds')) {
    /**
     * Get OTP resend cooldown in seconds
     */
    function getOtpResendSeconds(): int
    {
        try {
            return SettingService::getOtpResendSeconds();
        } catch (\Exception $e) {
            return 60; // Default fallback
        }
    }
}
