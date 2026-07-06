<?php

namespace App\Support;

class DisplayVoiceAnnouncement
{
    public static function placeholders(): array
    {
        return [
            '{token_number}',
            '{patient_name}',
            '{doctor_name}',
            '{room_number}',
            '{time_slot}',
        ];
    }

    public static function placeholdersHelpText(): string
    {
        return 'Available placeholders: ' . implode(', ', static::placeholders()) . '.';
    }

    public static function defaultTemplate(): string
    {
        return 'Token {token_number}, please proceed to Room {room_number}, Dr. {doctor_name}.';
    }

    public static function languageHelpText(): string
    {
        return 'Recommended language codes: en-US (English US), en-IN (English India), hi-IN (Hindi), pa-IN (Punjabi). Voice availability depends on each browser and device.';
    }
}
