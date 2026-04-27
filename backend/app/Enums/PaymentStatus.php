<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING  = 'pending';
    case PAID     = 'paid';
    case FAILED   = 'failed';
    case REFUNDED = 'refunded';

    public static function values(): array
    {
        return [
            self::PENDING->value  => 'Pending',
            self::PAID->value     => 'Paid',
            self::FAILED->value   => 'Failed',
            self::REFUNDED->value => 'Refunded',
        ];
    }
    public function label(): string
    {
        return match ($this) {
            self::PENDING  => 'Pending',
            self::PAID     => 'Paid',
            self::FAILED   => 'Failed',
            self::REFUNDED => 'Refunded',
        };
    }
    public static function default(): string
    {
        return self::PENDING->value;
    }
}
