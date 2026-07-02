<?php

namespace App\Enums;

enum LanguageOption: string
{
    case ENGLISH   = 'English';
    case HINDI     = 'Hindi';
    case BENGALI   = 'Bengali';
    case MARATHI   = 'Marathi';
    case GUJARATI  = 'Gujarati';
    case TAMIL     = 'Tamil';
    case TELUGU    = 'Telugu';
    case KANNADA   = 'Kannada';
    case PUNJABI   = 'Punjabi';
    case URDU      = 'Urdu';
    case ODIA      = 'Odia';
    case MALAYALAM = 'Malayalam';
    case KASHMIRI  = 'Kashmiri';
    case OTHER     = 'Other';

    // Return label-value pairs for frontend
    public static function labels(): array
    {
        return [
            'English'   => self::ENGLISH->value,
            'Hindi'     => self::HINDI->value,
            'Bengali'   => self::BENGALI->value,
            'Marathi'   => self::MARATHI->value,
            'Gujarati'  => self::GUJARATI->value,
            'Tamil'     => self::TAMIL->value,
            'Telugu'    => self::TELUGU->value,
            'Kannada'   => self::KANNADA->value,
            'Punjabi'   => self::PUNJABI->value,
            'Urdu'      => self::URDU->value,
            'Odia'      => self::ODIA->value,
            'Malayalam' => self::MALAYALAM->value,
            'Kashmiri'  => self::KASHMIRI->value,
            'Other'     => self::OTHER->value,
        ];
    }
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function default(): string
    {
        return self::ENGLISH->value;
    }
}