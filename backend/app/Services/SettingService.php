<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class SettingService
{
    /*
    |--------------------------------------------------------------------------
    | Application Settings
    |--------------------------------------------------------------------------
    */

    public static function getAppName(): string
    {
        return Setting::getValue('app', 'name', config('app.name', 'Telehealth Deploymeta'));
    }

    public static function getAppVersion(): string
    {
        return Setting::getValue('app', 'version', '1.0.0');
    }

    public static function getPrimaryColor(): string
    {
        return Setting::getValue('app', 'primary_color', '#055bd9');
    }

    public static function getSecondaryColor(): string
    {
        return Setting::getValue('app', 'secondary_color', '#055bd9');
    }

    /**
     * Get all theme colors for CSS/JS use
     */
    public static function getThemeColors(): array
    {
        return [
            'primary' => self::getPrimaryColor(),
            'secondary' => self::getSecondaryColor(),
        ];
    }

    public static function getLogo(): ?string
    {
        $logo = Setting::getValue('app', 'logo');
        if ($logo && Storage::disk('public')->exists($logo)) {
            return Storage::disk('public')->url($logo);
        }
        return null;
    }

    public static function getFavicon(): ?string
    {
        $favicon = Setting::getValue('app', 'favicon');
        if ($favicon && Storage::disk('public')->exists($favicon)) {
            return Storage::disk('public')->url($favicon);
        }
        return null;
    }

    public static function getGlobalStamp(): ?string
    {
        $stamp = Setting::getValue('app', 'global_stamp');
        if ($stamp && Storage::disk('public')->exists($stamp)) {
            return Storage::disk('public')->url($stamp);
        }
        return null;
    }

    public static function getContactInfo(): array
    {
        return [
            'email' => Setting::getValue('app', 'email'),
            'phone' => Setting::getValue('app', 'phone'),
            'support_email' => Setting::getValue('app', 'support_email'),
            'address' => Setting::getValue('app', 'address'),
        ];
    }

    public static function getConsultationSupportInfo(
        ?string $consultationType = 'video'
    ): array {

        $consultationType = strtolower(trim($consultationType));

        $section = match ($consultationType) {

            'in-person',
            'inperson',
            'offline' => 'inperson_consultation',

            'video',
            'online' => 'video_consultation',

            default => 'video_consultation',
        };

        return [
            'phone' => Setting::getValue(
                'support',
                "{$section}.phone"
            ),

            'support_email' => Setting::getValue(
                'support',
                "{$section}.support_email"
            ),

            'address' => Setting::getValue(
                'support',
                "{$section}.address"
            ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    */

    public static function isRazorpayEnabled(): bool
    {
        return (bool) Setting::getValue('third_party', 'razorpay_enabled', false);
    }

    public static function getRazorpayKey(): ?string
    {
        // First check database, then fall back to .env
        $dbValue = Setting::getValue('third_party', 'razorpay_key_id');
        return $dbValue ?: env('RAZORPAY_KEY_ID');
    }

    public static function getRazorpaySecret(): ?string
    {
        // First check database, then fall back to .env
        $dbValue = Setting::getValue('third_party', 'razorpay_key_secret');
        return $dbValue ?: env('RAZORPAY_KEY_SECRET');
    }

    public static function getCurrency(): string
    {
        return Setting::getValue('payment', 'currency', 'INR');
    }

    public static function getCurrencySymbol(): string
    {
        return Setting::getValue('payment', 'currency_symbol', '₹');
    }

    public static function isAppointmentMockPaymentEnabled(): bool
    {
        return (bool) Setting::getValue(
            'third_party',
            'mock_booking_enabled',
            env('APPOINTMENT_MOCK_PAYMENT_ENABLED', false)
        );
    }

    public static function getChildAgeLimit(): int
    {
        $age = (int) Setting::getValue(
            'booking',
            'child_age',
            config('settings.booking.sections.rules.fields.child_age.default', 12)
        );

        return max(1, $age);
    }

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */

    public static function getMaxLoginAttempts(): int
    {
        // Unlimited login attempts
        return PHP_INT_MAX;
    }

    public static function getLockoutDuration(): int
    {
        return (int) Setting::getValue('security', 'lockout_duration', 30);
    }

    public static function getPasswordMinLength(): int
    {
        return (int) Setting::getValue('security', 'password_min_length', 8);
    }

    /*
    |--------------------------------------------------------------------------
    | OTP Settings
    |--------------------------------------------------------------------------
    */

    public static function isOtpEnabled(): bool
    {
        return (bool) config('otp.enabled', true);
    }

    public static function getOtpLength(): int
    {
        return (int) config('otp.length', 6);
    }

    public static function getOtpExpiryMinutes(): int|float
    {
        return (int) config('otp.expiry_seconds', 60) / 60;
    }

    public static function getOtpExpirySeconds(): int
    {
        return (int) config('otp.expiry_seconds', 60);
    }

    public static function getOtpExpiryTime(): int
    {
        return (int) config('otp.expiry_seconds', 60);
    }

    public static function getOtpExpiryUnit(): string
    {
        return config('otp.expiry_unit', 'seconds');
    }

    public static function getOtpResendSeconds(): int
    {
        return (int) config('otp.resend_seconds', 60);
    }

    public static function getOtpSettings(): array
    {
        return [
            'enabled' => self::isOtpEnabled(),
            'length' => self::getOtpLength(),
            'expiry_time' => self::getOtpExpiryTime(),
            'expiry_unit' => self::getOtpExpiryUnit(),
            'resend_seconds' => self::getOtpResendSeconds(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | About Us. Faq, Term & Condition and Privacy Policy Screen Content
    |--------------------------------------------------------------------------
    */

    public static function getFaqContent(): array
    {
        $faq_data = Setting::getValue('faq', 'faq_items', []);

        if (is_array($faq_data)) {
            foreach ($faq_data as &$item) {
                if (isset($item['icon']) && $item['icon'] && Storage::disk('public')->exists($item['icon'])) {
                    $item['icon'] = Storage::disk('public')->url($item['icon']);
                }
            }
        }

        return $faq_data;
    }

    public static function getAboutUsContent(): string
    {
        return Setting::getValue('about_us', 'about_us_field', 'About Us');
    }

    public static function getTermAndConditionsContent(): string
    {
        return Setting::getValue('term_and_conditions', 'term_and_condition_field', 'Term and Conditions');
    }

    public static function getPrivacyAndPolicyContent(): string
    {
        return Setting::getValue('privacy_and_policy', 'privacy_and_policy_field', 'Privacy and Policy');
    }

    /*
    |--------------------------------------------------------------------------
    | Mobile App Settings
    |--------------------------------------------------------------------------
    */

    public static function isForceUpdateEnabled(): bool
    {
        return (bool) Setting::getValue('mobile', 'force_update', false);
    }

    public static function getMobileAppSettings(): array
    {
        return [
            'force_update' => self::isForceUpdateEnabled(),
            'min_android_version' => Setting::getValue('mobile', 'min_android_version', '1.0.0'),
            'min_ios_version' => Setting::getValue('mobile', 'min_ios_version', '1.0.0'),
            'latest_android_version' => Setting::getValue('mobile', 'latest_android_version', '1.0.0'),
            'latest_ios_version' => Setting::getValue('mobile', 'latest_ios_version', '1.0.0'),
            'play_store_url' => Setting::getValue('mobile', 'play_store_url'),
            'app_store_url' => Setting::getValue('mobile', 'app_store_url'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Public Settings (For API)
    |--------------------------------------------------------------------------
    */

    public static function getPublicSettings(): array
    {
        return [
            'app' => [
                'name' => self::getAppName(),
                'version' => self::getAppVersion(),
                'logo' => self::getLogo(),
                'primary_color' => self::getPrimaryColor(),
                'secondary_color' => self::getSecondaryColor(),
                'contact' => self::getContactInfo(),
            ],
            'mobile' => self::getMobileAppSettings(),
            'otp' => self::getOtpSettings(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Profile Screen Content (For API)
    |--------------------------------------------------------------------------
    */
    public static function getProfileScreenContent(): array
    {
        return [
            'faq' => self::getFaqContent(),
            'about_us' => self::getAboutUsContent(),
            'term_and_conditions' => self::getTermAndConditionsContent(),
            'privacy_policy' => self::getPrivacyAndPolicyContent(),
        ];
    }
}
