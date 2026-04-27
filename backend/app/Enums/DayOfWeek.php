<?php

namespace App\Enums;

enum DayOfWeek: string
{
    case MONDAY = 'monday';
    case TUESDAY = 'tuesday';
    case WEDNESDAY = 'wednesday';
    case THURSDAY = 'thursday';
    case FRIDAY = 'friday';
    case SATURDAY = 'saturday';
    case SUNDAY = 'sunday';

    public static function labels(): array
    {
        return [
            self::MONDAY->value => 'Monday',
            self::TUESDAY->value => 'Tuesday',
            self::WEDNESDAY->value => 'Wednesday',
            self::THURSDAY->value => 'Thursday',
            self::FRIDAY->value => 'Friday',
            self::SATURDAY->value => 'Saturday',
            self::SUNDAY->value => 'Sunday',
        ];
    }

    public static function ordered(): array
    {
        return [
            self::MONDAY->value,
            self::TUESDAY->value,
            self::WEDNESDAY->value,
            self::THURSDAY->value,
            self::FRIDAY->value,
            self::SATURDAY->value,
            self::SUNDAY->value,
        ];
    }

    public static function keys(): array
    {
        // return all string keys
        return array_keys(self::labels());
    }
}
