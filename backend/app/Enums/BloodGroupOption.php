<?php

namespace App\Enums;

enum BloodGroupOption: string
{
    case A_POSITIVE  = 'A+';
    case A_NEGATIVE  = 'A-';
    case B_POSITIVE  = 'B+';
    case B_NEGATIVE  = 'B-';
    case O_POSITIVE  = 'O+';
    case O_NEGATIVE  = 'O-';
    case AB_POSITIVE = 'AB+';
    case AB_NEGATIVE = 'AB-';

    // Return label-value pairs for frontend
    public static function labels(): array
    {
        return [
            'A+'  => self::A_POSITIVE->value,
            'A-'  => self::A_NEGATIVE->value,
            'B+'  => self::B_POSITIVE->value,
            'B-'  => self::B_NEGATIVE->value,
            'O+'  => self::O_POSITIVE->value,
            'O-'  => self::O_NEGATIVE->value,
            'AB+' => self::AB_POSITIVE->value,
            'AB-' => self::AB_NEGATIVE->value,
        ];
    }
}
