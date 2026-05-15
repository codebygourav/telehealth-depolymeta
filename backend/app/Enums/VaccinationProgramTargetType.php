<?php

namespace App\Enums;

enum VaccinationProgramTargetType: string
{
    case BABY = 'baby';
    case PREGNANCY = 'pregnancy';
    case CHILD = 'child';
    case ADULT = 'adult';
    case ELDERLY = 'elderly';
    case TRAVEL = 'travel';

    public function label(): string
    {
        return match ($this) {
            self::BABY => 'Baby Immunization',
            self::PREGNANCY => 'Pregnancy Vaccination',
            self::CHILD => 'Child Vaccination',
            self::ADULT => 'Adult Vaccination',
            self::ELDERLY => 'Elderly Vaccination',
            self::TRAVEL => 'Travel Vaccination',
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
