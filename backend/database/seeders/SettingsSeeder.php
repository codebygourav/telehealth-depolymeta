<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Application
            ['group' => 'app', 'key' => 'name', 'value' => 'Telehealth Deploymeta', 'type' => 'string', 'is_public' => true],
            ['group' => 'app', 'key' => 'tagline', 'value' => 'Your Trusted Healthcare Partner', 'type' => 'string', 'is_public' => true],
            ['group' => 'app', 'key' => 'version', 'value' => '1.0.0', 'type' => 'string', 'is_public' => true],
            ['group' => 'app', 'key' => 'primary_color', 'value' => '#055bd9', 'type' => 'string', 'is_public' => true],
            ['group' => 'app', 'key' => 'booking_cutoff_rules', 'value' => '[{"value":4,"unit":"hours"},{"value":1,"unit":"hours"}]', 'type' => 'string', 'is_public' => true],
            ['group' => 'app', 'key' => 'secondary_color', 'value' => '#22c55e', 'type' => 'string', 'is_public' => true],
            ['group' => 'app', 'key' => 'email', 'value' => 'info@cmctelehealth.com', 'type' => 'string', 'is_public' => true],
            ['group' => 'app', 'key' => 'phone', 'value' => '+91 1234567890', 'type' => 'string', 'is_public' => true],
            ['group' => 'app', 'key' => 'global_stamp', 'value' => 'settings/global_stamp.png', 'type' => 'string', 'is_public' => true],

            ['group' => 'booking', 'key' => 'child_age', 'value' => '12', 'type' => 'integer', 'is_public' => true],

            // Mail - Load from .env by default
            ['group' => 'mail', 'key' => 'host', 'value' => env('MAIL_HOST', ''), 'type' => 'string', 'is_public' => false],
            ['group' => 'mail', 'key' => 'port', 'value' => env('MAIL_PORT', '587'), 'type' => 'string', 'is_public' => false],
            ['group' => 'mail', 'key' => 'username', 'value' => env('MAIL_USERNAME', ''), 'type' => 'string', 'is_public' => false],
            ['group' => 'mail', 'key' => 'password', 'value' => env('MAIL_PASSWORD', ''), 'type' => 'string', 'is_public' => false],
            ['group' => 'mail', 'key' => 'encryption', 'value' => env('MAIL_ENCRYPTION', 'tls'), 'type' => 'string', 'is_public' => false],
            ['group' => 'mail', 'key' => 'from_address', 'value' => env('MAIL_FROM_ADDRESS', 'noreply@cmctelehealth.com'), 'type' => 'string', 'is_public' => false],
            ['group' => 'mail', 'key' => 'from_name', 'value' => env('MAIL_FROM_NAME', 'Telehealth Deploymeta'), 'type' => 'string', 'is_public' => false],

            // Payment
            ['group' => 'payment', 'key' => 'currency', 'value' => 'INR', 'type' => 'string', 'is_public' => true],
            ['group' => 'payment', 'key' => 'currency_symbol', 'value' => '₹', 'type' => 'string', 'is_public' => true],
            ['group' => 'payment', 'key' => 'razorpay_enabled', 'value' => env('RAZORPAY_KEY') ? '1' : '0', 'type' => 'boolean', 'is_public' => false],
            ['group' => 'payment', 'key' => 'razorpay_key', 'value' => env('RAZORPAY_KEY', ''), 'type' => 'string', 'is_public' => false],
            ['group' => 'payment', 'key' => 'razorpay_secret', 'value' => env('RAZORPAY_SECRET', ''), 'type' => 'string', 'is_public' => false],

            // Security
            ['group' => 'security', 'key' => 'max_login_attempts', 'value' => '5', 'type' => 'integer', 'is_public' => false],
            ['group' => 'security', 'key' => 'lockout_duration', 'value' => '30', 'type' => 'integer', 'is_public' => false],


            // Mobile
            ['group' => 'mobile', 'key' => 'force_update', 'value' => '0', 'type' => 'boolean', 'is_public' => true],
            ['group' => 'mobile', 'key' => 'min_android_version', 'value' => '1.0.0', 'type' => 'string', 'is_public' => true],
            ['group' => 'mobile', 'key' => 'min_ios_version', 'value' => '1.0.0', 'type' => 'string', 'is_public' => true],
            ['group' => 'mobile', 'key' => 'latest_android_version', 'value' => '1.0.0', 'type' => 'string', 'is_public' => true],
            ['group' => 'mobile', 'key' => 'latest_ios_version', 'value' => '1.0.0', 'type' => 'string', 'is_public' => true],

            // Advanced
            ['group' => 'advanced', 'key' => 'debug_mode', 'value' => '0', 'type' => 'boolean', 'is_public' => false],

            ['group' => 'support', 'key' => 'video_consultation.phone', 'value' => '+91 1234567890', 'type' => 'string', 'is_public' => false],
            ['group' => 'support', 'key' => 'video_consultation.support_email', 'value' => 'video-support@example.com', 'type' => 'string', 'is_public' => false],
            ['group' => 'support', 'key' => 'video_consultation.address', 'value' => 'Video Consultation Support Address', 'type' => 'string', 'is_public' => false],

            ['group' => 'support', 'key' => 'inperson_consultation.phone', 'value' => '+91 1234567890', 'type' => 'string', 'is_public' => false],
            ['group' => 'support', 'key' => 'inperson_consultation.support_email', 'value' => 'inperson-support@example.com', 'type' => 'string', 'is_public' => false],
            ['group' => 'support', 'key' => 'inperson_consultation.address', 'value' => 'In-Person Consultation Support Address', 'type' => 'string', 'is_public' => false],

            // Display Screen
            ['group' => 'display', 'key' => 'password', 'value' => 'opt-token', 'type' => 'string', 'is_public' => false],
            ['group' => 'display', 'key' => 'doctor_mode', 'value' => 'all', 'type' => 'string', 'is_public' => false],
            ['group' => 'display', 'key' => 'selected_doctors', 'value' => '[]', 'type' => 'json', 'is_public' => false],
            ['group' => 'display', 'key' => 'selected_doctors_rotation_seconds', 'value' => '12', 'type' => 'integer', 'is_public' => false],
            ['group' => 'display', 'key' => 'slide_seconds', 'value' => '8', 'type' => 'integer', 'is_public' => false],
            ['group' => 'display', 'key' => 'refresh_seconds', 'value' => '30', 'type' => 'integer', 'is_public' => false],
            ['group' => 'display', 'key' => 'page_title', 'value' => 'Opt Token Display', 'type' => 'string', 'is_public' => false],
            ['group' => 'display', 'key' => 'page_subtitle', 'value' => 'Please keep your token ready and be seated.', 'type' => 'string', 'is_public' => false],
            ['group' => 'display', 'key' => 'queue_label', 'value' => 'Today\'s Queue', 'type' => 'string', 'is_public' => false],
            ['group' => 'display', 'key' => 'queue_subtitle', 'value' => 'Current token, next patient and queue position', 'type' => 'string', 'is_public' => false],
            ['group' => 'display', 'key' => 'advertisement_badge', 'value' => 'Doctor Advertisement Slider', 'type' => 'string', 'is_public' => false],
            ['group' => 'display', 'key' => 'now_showing_label', 'value' => 'Now showing', 'type' => 'string', 'is_public' => false],
            ['group' => 'display', 'key' => 'cta_label', 'value' => 'Please keep your token ready', 'type' => 'string', 'is_public' => false],
            ['group' => 'display', 'key' => 'empty_state_title', 'value' => 'No active doctor assigned', 'type' => 'string', 'is_public' => false],
            ['group' => 'display', 'key' => 'empty_state_text', 'value' => 'Assign one or more doctors in the display settings to start the live board.', 'type' => 'string', 'is_public' => false],

            // Display Ads
            ['group' => 'display_ads', 'key' => 'randomize_bottom_content', 'value' => '1', 'type' => 'boolean', 'is_public' => false],
            ['group' => 'display_ads', 'key' => 'slide_duration_seconds', 'value' => '8', 'type' => 'integer', 'is_public' => false],
            ['group' => 'display_ads', 'key' => 'doctor_rotation_seconds', 'value' => '12', 'type' => 'integer', 'is_public' => false],
            ['group' => 'display_ads', 'key' => 'refresh_seconds', 'value' => '30', 'type' => 'integer', 'is_public' => false],
            ['group' => 'display_ads', 'key' => 'pause_between_doctors_seconds', 'value' => '2', 'type' => 'integer', 'is_public' => false],
            ['group' => 'display_ads', 'key' => 'badge_label', 'value' => 'Doctor Advertisement Slider', 'type' => 'string', 'is_public' => false],
            ['group' => 'display_ads', 'key' => 'now_showing_label', 'value' => 'Now showing', 'type' => 'string', 'is_public' => false],
            ['group' => 'display_ads', 'key' => 'empty_slide_title', 'value' => 'No advertisement assigned', 'type' => 'string', 'is_public' => false],
            ['group' => 'display_ads', 'key' => 'empty_slide_text', 'value' => 'Add at least one active advertisement for this doctor.', 'type' => 'string', 'is_public' => false],
            ['group' => 'display_ads', 'key' => 'cta_label', 'value' => 'Please keep your token ready', 'type' => 'string', 'is_public' => false],
            ['group' => 'display_ads', 'key' => 'bottom_content_label', 'value' => 'Health Updates', 'type' => 'string', 'is_public' => false],
            ['group' => 'display_ads', 'key' => 'next_patient_label', 'value' => 'Next Patient', 'type' => 'string', 'is_public' => false],
            ['group' => 'display_ads', 'key' => 'popup_title', 'value' => 'Next Patient Alert', 'type' => 'string', 'is_public' => false],

            // Third Party
            ['group' => 'third_party', 'key' => 'razorpay_enabled', 'value' => env('RAZORPAY_KEY') ? '1' : '0', 'type' => 'boolean', 'is_public' => false],
            ['group' => 'third_party', 'key' => 'razorpay_key_id', 'value' => env('RAZORPAY_KEY_ID', ''), 'type' => 'string', 'is_public' => false],
            ['group' => 'third_party', 'key' => 'razorpay_key_secret', 'value' => env('RAZORPAY_KEY_SECRET', ''), 'type' => 'string', 'is_public' => false],
            ['group' => 'third_party', 'key' => 'mock_booking_enabled', 'value' => env('APPOINTMENT_MOCK_PAYMENT_ENABLED', false) ? '1' : '0', 'type' => 'boolean', 'is_public' => false],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'is_public' => $setting['is_public'],
                ]
            );
        }
    }
}
