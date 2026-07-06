<?php

namespace App\Support;

use App\Models\Setting;

class PrescriptionSpeech
{
    public const DEFAULT_LANGUAGE = 'en';

    public static function defaultTemplate(): string
    {
        return 'Medicine {item_number}: {medicine_name}. {dosage_sentence} {schedule_sentence} {timing_sentence} {meal_timing_sentence} {duration_sentence} {instructions_sentence} {reason_sentence} {min_gap_sentence} {max_doses_sentence}';
    }

    public static function placeholders(): array
    {
        return [
            '{item_number}',
            '{medicine_name}',
            '{medicine_type}',
            '{dosage}',
            '{dosage_sentence}',
            '{use_type_label}',
            '{frequency_label}',
            '{schedule_sentence}',
            '{timing_list}',
            '{timing_sentence}',
            '{meal_timing_label}',
            '{meal_timing_sentence}',
            '{duration_label}',
            '{duration_sentence}',
            '{instructions}',
            '{instructions_sentence}',
            '{take_when}',
            '{reason_sentence}',
            '{min_gap}',
            '{min_gap_sentence}',
            '{max_doses_per_day}',
            '{max_doses_sentence}',
        ];
    }

    public static function placeholdersHelpText(): string
    {
        return 'Available placeholders: ' . implode(', ', static::placeholders()) . '. Free-text medicine names and instructions are spoken exactly as saved.';
    }

    public static function settings(): array
    {
        $defaultTemplate = static::defaultTemplate();
        $template = null;

        foreach (['template', 'template_en', 'template_hi', 'template_pa'] as $key) {
            $candidate = Setting::getValue('prescription_voice', $key);

            if (is_string($candidate) && trim($candidate) !== '') {
                $template = $candidate;
                break;
            }
        }

        if (! is_string($template) || trim($template) === '') {
            $template = $defaultTemplate;
        }

        return [
            'enabled' => (bool) Setting::getValue('prescription_voice', 'enabled', true),
            'default_language' => (string) Setting::getValue('prescription_voice', 'default_language', static::DEFAULT_LANGUAGE),
            'template' => (string) $template,
            'placeholders' => static::placeholders(),
            'placeholders_help' => static::placeholdersHelpText(),
        ];
    }
}
