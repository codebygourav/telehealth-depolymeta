<?php

namespace App\Enums;

enum VaccinationStatus: string
{
    case UPCOMING = 'upcoming';
    case DUE_SOON = 'due_soon';
    case DUE_TODAY = 'due_today';
    case OVERDUE = 'overdue';
    case COMPLETED = 'completed';
    case MISSED = 'missed';
    case RESCHEDULED = 'rescheduled';
    case ON_HOLD = 'on_hold';
    case CANCELLED = 'cancelled';
    case SKIPPED_BY_DOCTOR = 'skipped_by_doctor';
    case PENDING_APPROVAL = 'pending_approval';

    // Keep legacy cases for backward compatibility if reference exists elsewhere
    case PENDING = 'pending';
    case SCHEDULED = 'scheduled';

    public function label(): string
    {
        return match ($this) {
            self::UPCOMING => 'Upcoming',
            self::DUE_SOON => 'Due Soon',
            self::DUE_TODAY => 'Due Today',
            self::OVERDUE => 'Overdue',
            self::MISSED => 'Missed',
            self::COMPLETED => 'Completed',
            self::RESCHEDULED => 'Rescheduled',
            self::ON_HOLD => 'On Hold',
            self::SKIPPED_BY_DOCTOR => 'Skipped',
            self::CANCELLED => 'Cancelled',
            self::PENDING_APPROVAL => 'Pending Approval',
            self::PENDING => 'Pending',
            self::SCHEDULED => 'Scheduled',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        $ordered = [
            self::UPCOMING,
            self::DUE_SOON,
            self::DUE_TODAY,
            self::OVERDUE,
            self::MISSED,
            self::COMPLETED,
            self::RESCHEDULED,
            self::ON_HOLD,
            self::SKIPPED_BY_DOCTOR,
            self::CANCELLED,
            self::PENDING_APPROVAL,
            self::PENDING,
            self::SCHEDULED,
        ];

        return collect($ordered)->mapWithKeys(fn (self $status) => [$status->value => $status->label()])->all();
    }
}
