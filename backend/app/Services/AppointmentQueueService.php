<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AppointmentQueueService
{
    public function resolveQueueStatus(Appointment $appointment): string
    {
        $queueStatus = strtolower((string) ($appointment->queue_status ?? ''));

        if (in_array($queueStatus, ['scheduled', 'checkin', 'started', 'completed', 'skipped', 'no_show'], true)) {
            return $queueStatus;
        }

        if ($queueStatus === 'waiting' || $queueStatus === '') {
            return 'scheduled';
        }

        return 'scheduled';
    }

    public function getNextInQueueText(Appointment $currentAppointment, Collection $allAppointments): string
    {
        $currentStatus = $this->resolveQueueStatus($currentAppointment);

        if ($currentStatus === 'completed') {
            $next = $allAppointments->first(function (Appointment $appointment): bool {
                return $this->resolveQueueStatus($appointment) === 'checkin';
            });

            return $next ? $next->queue_number : '-';
        }

        if ($currentStatus === 'started') {
            $next = $allAppointments->first(function (Appointment $appointment): bool {
                return $this->resolveQueueStatus($appointment) === 'checkin';
            });

            return $next ? "Next: " . $next->queue_number : '-';
        }

        if ($currentStatus === 'checkin') {
            $running = $allAppointments->first(function (Appointment $appointment): bool {
                return $this->resolveQueueStatus($appointment) === 'started';
            });

            if ($running) {
                return "After " . $running->queue_number;
            }

            $firstWaiting = $allAppointments->first(function (Appointment $appointment): bool {
                return $this->resolveQueueStatus($appointment) === 'checkin';
            });

            if ($firstWaiting && $firstWaiting->id === $currentAppointment->id) {
                return 'Ready to Start';
            }

            return 'In Queue';
        }

        if ($currentStatus === 'scheduled') {
            if (! $this->isWithinScheduledActionWindow($currentAppointment, 60)) {
                return 'Starts 1 hour before time';
            }

            $ordered = $allAppointments->values();
            $currentIndex = $ordered->search(fn (Appointment $appointment): bool => $appointment->id === $currentAppointment->id);

            if ($currentIndex !== false) {
                $next = $ordered->slice($currentIndex + 1)->first(function (Appointment $appointment): bool {
                    $status = $this->resolveQueueStatus($appointment);

                    return in_array($status, ['scheduled', 'checkin'], true);
                });

                return $next ? "Next: " . $next->queue_number : '-';
            }

            return '-';
        }

        if ($currentStatus === 'skipped') {
            return 'Can re-queue after current';
        }

        if ($currentStatus === 'no_show') {
            return 'Booked but not checked-in';
        }

        return '-';
    }

    public function getPopupAppointment(Appointment $currentAppointment, Collection $allAppointments): ?Appointment
    {
        $currentStatus = $this->resolveQueueStatus($currentAppointment);

        if ($currentStatus === 'started' || $currentStatus === 'completed') {
            return $allAppointments->first(function (Appointment $appointment): bool {
                return $this->resolveQueueStatus($appointment) === 'checkin';
            });
        }

        if ($currentStatus === 'checkin') {
            return $allAppointments->first(function (Appointment $appointment): bool {
                return $this->resolveQueueStatus($appointment) === 'started';
            }) ?? $currentAppointment;
        }

        if ($currentStatus === 'scheduled') {
            if (! $this->isWithinScheduledActionWindow($currentAppointment, 60)) {
                return null;
            }

            $ordered = $allAppointments->values();
            $currentIndex = $ordered->search(fn (Appointment $appointment): bool => $appointment->id === $currentAppointment->id);

            if ($currentIndex === false) {
                return $currentAppointment;
            }

            return $ordered->slice($currentIndex + 1)->first(function (Appointment $appointment): bool {
                $status = $this->resolveQueueStatus($appointment);

                return in_array($status, ['scheduled', 'checkin'], true);
            }) ?? $currentAppointment;
        }

        return $currentAppointment;
    }

    public function isWithinScheduledActionWindow(Appointment $appointment, int $minutes = 60): bool
    {
        $appointmentDateTime = $this->getAppointmentDateTime($appointment);

        if (! $appointmentDateTime) {
            return false;
        }

        $now = Carbon::now();

        return $now->isSameDay($appointmentDateTime)
            && $now->greaterThanOrEqualTo($appointmentDateTime->copy()->subMinutes($minutes));
    }

    protected function getAppointmentDateTime(Appointment $appointment): ?Carbon
    {
        if (! $appointment->appointment_date || ! $appointment->appointment_time) {
            return null;
        }

        $date = $appointment->appointment_date instanceof Carbon
            ? $appointment->appointment_date->copy()
            : Carbon::parse($appointment->appointment_date);

        return Carbon::parse($date->toDateString() . ' ' . $appointment->appointment_time);
    }
}
