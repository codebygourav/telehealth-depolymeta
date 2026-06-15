<?php


namespace App\Enums;

enum AppointmentStatus: string
{
    case PENDING     = 'pending';
    case CONFIRMED  = 'confirmed';
    case COMPLETED  = 'completed';
    case RESCHEDULED = 'rescheduled';
    case CANCELLED  = 'cancelled';
    case NO_SHOW    = 'no_show';
    case FAILED     = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING      => 'Pending',
            self::CONFIRMED   => 'Confirmed',
            self::COMPLETED   => 'Completed',
            self::RESCHEDULED => 'Rescheduled',
            self::CANCELLED   => 'Cancelled',
            self::NO_SHOW     => 'No Show',
            self::FAILED      => 'Failed',
        };
    }
    public static function default(): string
    {
        return self::PENDING->value;
    }
    public static function equals($status, self $compareTo): bool
    {
        if ($status instanceof self) {
            return $status === $compareTo;
        }
        return $status === $compareTo->value;
    }
}
