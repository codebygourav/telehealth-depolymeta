<?php

namespace App\Enums;

enum GenderOption: string
{
    case MALE = 'male';
    case FEMALE = 'female';
    case OTHER = 'other';

    public static function labels(): array
    {
        return [
            self::MALE->value => 'Male',
            self::FEMALE->value => 'Female',
            self::OTHER->value => 'Other',
        ];
    }
}
