<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AppointmentQueueService
{
    public function doctorQueueQuery(string $doctorId, ?Carbon $date = null): Builder
    {
        $targetDate = ($date ?? Carbon::today())->toDateString();

        return Appointment::query()
            ->with([
                'patient.user:id,name',
                'doctor:id,first_name,last_name',
                'availability:id,doctor_room',
            ])
            ->where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $targetDate)
            ->whereNotIn('status', [
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::FAILED->value,
            ]);
    }

    public function applyStatusFilter(Builder $query, string $statusFilter): Builder
    {
        if ($statusFilter === 'all') {
            return $query;
        }

        if ($statusFilter === 'scheduled') {
            return $query->where(function (Builder $builder): void {
                $builder->whereNull('queue_status')
                    ->orWhereIn('queue_status', ['scheduled', 'waiting']);
            });
        }

        if ($statusFilter === 'passed_completed') {
            $currentTime = now()->format('H:i:s');

            return $query->where(function (Builder $builder) use ($currentTime): void {
                $builder->where('queue_status', 'completed')
                    ->orWhere(function (Builder $passed) use ($currentTime): void {
                        $passed->where(function (Builder $statusQuery): void {
                            $statusQuery->whereNull('queue_status')
                                ->orWhereIn('queue_status', ['scheduled', 'waiting', 'checkin', 'started', 'skipped', 'no_show']);
                        })->where(function (Builder $timeQuery) use ($currentTime): void {
                            $timeQuery->where(function (Builder $withEndTime) use ($currentTime): void {
                                $withEndTime->whereNotNull('appointment_end_time')
                                    ->where('appointment_end_time', '<', $currentTime);
                            })->orWhere(function (Builder $withoutEndTime) use ($currentTime): void {
                                $withoutEndTime->whereNull('appointment_end_time')
                                    ->where('appointment_time', '<', $currentTime);
                            });
                        });
                    });
            });
        }

        return $query->where('queue_status', $statusFilter);
    }

    public function getDoctorQueueAppointments(string $doctorId, ?Carbon $date = null): Collection
    {
        return $this->sortAppointments(
            $this->doctorQueueQuery($doctorId, $date)->get()
        );
    }

    public function filterAppointmentsForDisplay(Collection $appointments): Collection
    {
        return $this->sortAppointments($appointments)
            ->filter(function (Appointment $appointment): bool {
                $status = $this->resolveQueueStatus($appointment);

                if (in_array($status, ['started', 'checkin', 'completed'], true)) {
                    return true;
                }

                return $status === 'scheduled'
                    && ($this->isWithinScheduledActionWindow($appointment, 60) || $this->isPassed($appointment));
            })
            ->values()
            ->take(12)
            ->values();
    }

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
            $next = $this->findNextCallableAppointment($allAppointments);

            return $next ? $next->queue_number : '-';
        }

        if ($currentStatus === 'started') {
            $next = $this->findNextCallableAppointment($allAppointments);

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
                return in_array($this->resolveQueueStatus($appointment), ['checkin', 'scheduled'], true);
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
            return $this->findNextCallableAppointment($allAppointments);
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

    public function isPassed(Appointment $appointment): bool
    {
        if (! $appointment->appointment_date) {
            return false;
        }

        $appointmentDate = $appointment->appointment_date instanceof Carbon
            ? $appointment->appointment_date->copy()
            : Carbon::parse($appointment->appointment_date);

        if (! Carbon::today()->isSameDay($appointmentDate)) {
            return false;
        }

        if (in_array($this->resolveQueueStatus($appointment), ['completed'], true)) {
            return false;
        }

        $endTime = $appointment->appointment_end_time ?: $appointment->appointment_time;

        if (! $endTime) {
            return false;
        }

        return Carbon::parse($appointmentDate->toDateString() . ' ' . $endTime)->lt(now());
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

    protected function findNextCallableAppointment(Collection $appointments): ?Appointment
    {
        $checkedIn = $appointments->first(function (Appointment $appointment): bool {
            return $this->resolveQueueStatus($appointment) === 'checkin';
        });

        if ($checkedIn) {
            return $checkedIn;
        }

        return $appointments->first(function (Appointment $appointment): bool {
            return $this->resolveQueueStatus($appointment) === 'scheduled';
        });
    }

    protected function sortAppointments(Collection $appointments): Collection
    {
        return $appointments
            ->sortBy(function (Appointment $appointment): array {
                $queueNumber = (string) ($appointment->queue_number ?? '');
                $digits = preg_replace('/\D+/', '', $queueNumber) ?: '999999';

                return [
                    (int) $digits,
                    (string) ($appointment->appointment_time ?? '23:59:59'),
                ];
            })
            ->values();
    }
}
