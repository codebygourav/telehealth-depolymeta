<?php

namespace App\Enums;

enum PatientProfileType: string
{
    case SELF = 'self';
    case BABY = 'baby';
    case PREGNANCY = 'pregnancy';
    case CHILD = 'child';
    case ADULT = 'adult';
    case ELDERLY = 'elderly';

    public function label(): string
    {
        return match ($this) {
            self::SELF => 'Self',
            self::BABY => 'Baby',
            self::PREGNANCY => 'Pregnancy',
            self::CHILD => 'Child',
            self::ADULT => 'Adult',
            self::ELDERLY => 'Elderly',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
