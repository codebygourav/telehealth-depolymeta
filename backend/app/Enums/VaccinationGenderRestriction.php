<?php

namespace App\Enums;

enum VaccinationGenderRestriction: string
{
    case ALL = 'all';
    case MALE = 'male';
    case FEMALE = 'female';

    public function label(): string
    {
        return match ($this) {
            self::ALL => 'All Patients',
            self::MALE => 'Male Only',
            self::FEMALE => 'Female Only',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $restriction) => [$restriction->value => $restriction->label()])
            ->all();
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
