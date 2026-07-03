<?php

namespace Database\Seeders;

use App\Models\DisplayScreenSetting;
use Illuminate\Database\Seeder;

class DisplayScreenSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'display' => [
                'screen_name' => 'Main OPD Waiting Hall',
                'screen_location' => 'Ground Floor OPD',
                'display_mode' => 'auto',
                'default_notice' => 'Please keep your token ready. Wait near your assigned OPD room.',
                'password' => '123',
                'doctor_mode' => 'all',
                'selected_doctors' => [],
                'selected_doctors_rotation_seconds' => 32,
                'refresh_seconds' => 20,
                'page_title' => 'Hospital Token Queue Display',
                'page_subtitle' => 'Live OPD updates, announcements, and doctor-specific content.',
                'queue_label' => 'Patients In Queue',
                'queue_subtitle' => 'Please stay near your assigned room and watch the current token.',
                'advertisement_badge' => 'Display Content',
                'now_showing_label' => 'Now Showing',
                'cta_label' => 'Please proceed when your token is called',
                'empty_state_title' => 'No active queue at the moment',
                'empty_state_text' => 'New patient calls and public notices will appear here.',
            ],
            'display_ads' => [
                'randomize_bottom_content' => true,
                'same_time_card_columns' => 2,
                'slide_seconds' => 8,
                'slide_duration_seconds' => 8,
                'doctor_rotation_seconds' => 12,
                'pause_between_doctors_seconds' => 2,
                'popup_enabled' => true,
                'popup_duration_seconds' => 8,
                'highlight_next_patient' => true,
                'voice_enabled' => true,
                'voice_language' => 'en-US',
                'voice_name' => '',
                'announcement_template' => 'Token {token_number}, please proceed to Room {room_number}, Dr. {doctor_name}.',
                'badge_label' => 'Display Content Slider',
                'empty_slide_title' => 'No display content assigned',
                'empty_slide_text' => 'Add at least one active display content item for this doctor or screen.',
                'bottom_content_label' => 'Health Updates',
                'next_patient_label' => 'Next Patient',
                'popup_title' => 'Next Patient Alert',
            ],
        ];

        foreach ($settings as $group => $items) {
            foreach ($items as $key => $value) {
                DisplayScreenSetting::setValue(
                    $group,
                    $key,
                    $value,
                    match (true) {
                        is_bool($value) => 'boolean',
                        is_int($value) => 'integer',
                        is_array($value) => 'json',
                        default => 'string',
                    },
                    null,
                    true,
                );
            }
        }
    }
}
