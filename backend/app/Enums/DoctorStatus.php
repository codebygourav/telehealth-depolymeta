<?php

namespace App\Enums;

enum DoctorStatus: string
{
    case PENDING_VERIFICATION = 'pending_verification';
    case REJECTED = 'rejected';
    case SUSPENDED = 'suspended';
    case ACTIVE = 'active';

    public static function values(): array
    {
        return [
            self::PENDING_VERIFICATION->value => 'Pending Verification',
            self::REJECTED->value => 'Rejected',
            self::SUSPENDED->value => 'Suspended',
            self::ACTIVE->value => 'Active',
        ];
    }

    public static function default(): string
    {
        return self::PENDING_VERIFICATION->value;
    }
}
