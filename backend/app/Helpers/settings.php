<?php

use App\Models\Setting;
use App\Services\SettingService;

if (!function_exists('setting')) {
    /**
     * Get a setting value
     * @param string $key Format: 'group.key' (e.g., 'app.name', 'payment.currency')
     */
    function setting(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key, 2);
        if (count($parts) !== 2) return $default;

        try {
            return Setting::getValue($parts[0], $parts[1], $default);
        } catch (\Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('app_name')) {
    function app_name(): string
    {
        return SettingService::getAppName();
    }
}

if (!function_exists('app_version')) {
    function app_version(): string
    {
        return SettingService::getAppVersion();
    }
}

if (!function_exists('app_logo')) {
    function app_logo(): ?string
    {
        return SettingService::getLogo();
    }
}

if (!function_exists('primary_color')) {
    function primary_color(): string
    {
        return SettingService::getPrimaryColor();
    }
}

if (!function_exists('secondary_color')) {
    function secondary_color(): string
    {
        return SettingService::getSecondaryColor();
    }
}

if (!function_exists('theme_colors')) {
    /**
     * Get all theme colors for use in CSS/JS
     */
    function theme_colors(): array
    {
        return SettingService::getThemeColors();
    }
}

if (!function_exists('currency_symbol')) {
    function currency_symbol(): string
    {
        return SettingService::getCurrencySymbol();
    }
}

if (!function_exists('currency_code')) {
    function currency_code(): string
    {
        return SettingService::getCurrency();
    }
}

if (!function_exists('format_price')) {
    function format_price(float $amount): string
    {
        return currency_symbol() . number_format($amount, 2);
    }
}

if (!function_exists('is_razorpay_enabled')) {
    function is_razorpay_enabled(): bool
    {
        return SettingService::isRazorpayEnabled();
    }
}

if (!function_exists('razorpay_key')) {
    function razorpay_key(): ?string
    {
        return SettingService::getRazorpayKey();
    }
}

if (!function_exists('otp_settings')) {
    function otp_settings(): array
    {
        return SettingService::getOtpSettings();
    }
}

if (!function_exists('mobile_app_settings')) {
    function mobile_app_settings(): array
    {
        return SettingService::getMobileAppSettings();
    }
}

if (!function_exists('play_store_url')) {
    function play_store_url(): string
    {
        return setting('mobile.play_store_url') ?: 'https://play.google.com/store/apps/details?id=com.cmctelehealth.app&hl=en';
    }
}

if (!function_exists('public_settings')) {
    /**
     * Get all public settings for API response
     */
    function public_settings(): array
    {
        return SettingService::getPublicSettings();
    }
}

if (!function_exists('contact_info')) {
    function contact_info(): array
    {
        return SettingService::getContactInfo();
    }
}