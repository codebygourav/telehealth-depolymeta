<?php

namespace App\Services\OpdToken;

use App\Models\DisplayScreen;
use App\Models\DisplayScreenSetting;
use App\Models\Setting;
use App\Support\DisplayVoiceAnnouncement;

class DisplaySettingsService
{
    public function load(?DisplayScreen $screen = null): array
    {
        $defaults = [
            'screen_name' => 'Main OPD Waiting Hall',
            'screen_location' => 'Ground Floor OPD',
            'display_mode' => 'auto',
            'default_notice' => 'Please keep your token ready. Wait near your assigned OPD room.',
            'password' => '',
            'doctor_mode' => 'all',
            'selected_doctors' => [],
            'show_doctor_list_from_appointments' => false,
            'refresh_seconds' => 30,
            'auto_reload_enabled' => false,
            'page_title' => 'OPD Token Display',
            'page_subtitle' => 'Please keep your token ready and be seated.',
            'queue_label' => "Today's Queue",
            'queue_subtitle' => 'Current token, next patient and queue position',
            'advertisement_badge' => 'Doctor Advertisement Slider',
            'now_showing_label' => 'Now showing',
            'cta_label' => 'Please keep your token ready',
            'empty_state_title' => 'No active doctor assigned',
            'empty_state_text' => 'Assign one or more doctors in the display settings to start the live board.',
            'show_ads_panel' => true,
            'randomize_bottom_content' => true,
            'slide_duration_seconds' => 8,
            'doctor_rotation_seconds' => 12,
            'pause_between_doctors_seconds' => 2,
            'popup_enabled' => true,
            'popup_duration_seconds' => 8,
            'ad_popup_enabled' => true,
            'ad_popup_interval_seconds' => 180,
            'ad_popup_duration_seconds' => 12,
            'ad_popup_title' => 'Doctor Spotlight',
            'voice_enabled' => true,
            'voice_language' => 'en-US',
            'voice_name' => '',
            'announcement_template' => DisplayVoiceAnnouncement::defaultTemplate(),
            'badge_label' => 'Doctor Advertisement Slider',
            'empty_slide_title' => 'No advertisement assigned',
            'empty_slide_text' => 'Add at least one active advertisement for this doctor.',
            'bottom_content_label' => 'Health Updates',
            'next_patient_label' => 'Next Patient',
            'popup_title' => 'Next Patient Alert',
            'same_time_card_columns' => 2,
            'show_slide_heading' => false,
            'show_slide_description' => false,
            'show_events_title' => false,
            'show_events_description' => false,
            'show_media_images' => true,
            'show_media_videos' => true,
        ];

        $screenDisplay = DisplayScreenSetting::getGroup('display');
        $screenAds = DisplayScreenSetting::getGroup('display_ads');

        if (empty($screenDisplay)) {
            $screenDisplay = Setting::getGroup('display');
        }

        if (empty($screenAds)) {
            $screenAds = Setting::getGroup('display_ads');
        }

        $config = array_merge($defaults, $screenDisplay, $screenAds);

        if ($screen) {
            $screenSettings = is_array($screen->settings) ? $screen->settings : [];

            $config = array_merge($config, [
                'screen_name' => $screen->name,
            ], $screenSettings);
        }

        foreach ([
            'show_ads_panel',
            'randomize_bottom_content',
            'popup_enabled',
            'ad_popup_enabled',
            'voice_enabled',
            'show_slide_heading',
            'show_slide_description',
            'show_events_title',
            'show_events_description',
            'show_media_images',
            'show_media_videos',
            'auto_reload_enabled',
        ] as $boolKey) {
            $config[$boolKey] = $this->toBool($config[$boolKey] ?? $defaults[$boolKey] ?? false);
        }

        $config['selected_doctors'] = collect($config['selected_doctors'] ?? [])
            ->filter()
            ->values()
            ->all();

        $config['screen_slug'] = $screen?->slug;
        $config['screen_id'] = $screen?->getKey();
        $config['screen_profile_name'] = $screen?->name;

        return $config;
    }

    protected function toBool(mixed $value, bool $default = false): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }
        }

        return $default;
    }
}
