<?php

namespace App\Support;

class PrescriptionDictation
{
    public const MODE_OFF = 'off';
    public const MODE_TEXT = 'text';
    public const MODE_SPEECH = 'speech';

    public static function settings(): array
    {
        $enabled = (bool) config('prescription_dictation.enabled', false);
        $inputMode = strtolower(trim((string) config('prescription_dictation.input_mode', self::MODE_OFF)));

        $inputMode = match ($inputMode) {
            'on', 'enabled', 'true', '1' => self::MODE_TEXT,
            'voice', 'mic', 'microphone' => self::MODE_SPEECH,
            default => $inputMode,
        };

        if (! $enabled) {
            $inputMode = self::MODE_OFF;
        }

        return [
            'enabled' => $enabled && in_array($inputMode, [self::MODE_TEXT, self::MODE_SPEECH], true),
            'input_mode' => $inputMode,
            'text_mode_max_chars' => (int) config('prescription_dictation.text_mode_max_chars', 1000),
            'speech_locale' => (string) config('prescription_dictation.speech_locale', 'en-IN'),
            'supported_locales' => array_values(config('prescription_dictation.supported_locales', ['auto', 'en-IN', 'en-US', 'hi-IN', 'pa-IN'])),
            'allow_custom_locale' => (bool) config('prescription_dictation.allow_custom_locale', true),
            'requires_doctor_review' => (bool) config('prescription_dictation.doctor_review_required', true),
            'single_medicine_only' => (bool) config('prescription_dictation.single_medicine_only', true),
        ];
    }

    public static function isTextModeEnabled(): bool
    {
        $settings = static::settings();

        return (bool) ($settings['enabled'] ?? false) && ($settings['input_mode'] ?? self::MODE_OFF) === self::MODE_TEXT;
    }

    public static function isDraftParsingEnabled(): bool
    {
        $settings = static::settings();

        return (bool) ($settings['enabled'] ?? false)
            && in_array($settings['input_mode'] ?? self::MODE_OFF, [self::MODE_TEXT, self::MODE_SPEECH], true);
    }
}
