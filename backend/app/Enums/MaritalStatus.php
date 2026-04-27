<?php

namespace App\Enums;

enum MaritalStatus: string
{
    case SINGLE = 'single';
    case MARRIED = 'married';
    case DIVORCED = 'divorced';
    case WIDOWED = 'widowed';

    // Return label-value pairs for frontend
    public static function labels(): array
    {
        return [
            self::SINGLE->value => 'Single',
            self::MARRIED->value => 'Married',
            self::DIVORCED->value => 'Divorced',
            self::WIDOWED->value => 'Widowed',
        ];
    }

    public static function default(): ?string
    {
        return null;
    }
}
