<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\DoctorAvailability;
use App\Models\ExternalBooking;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class SlotCapacityService
{
    public function activeAppointmentStatusesToExclude(): array
    {
        return [
            AppointmentStatus::CANCELLED->value,
            AppointmentStatus::FAILED->value,
        ];
    }

    public function bookedCount(
        string $doctorId,
        Carbon|string $date,
        Carbon|string $startTime,
        ?string $availabilityId = null,
        ?string $consultationType = null,
        ?string $excludeAppointmentId = null,
    ): int {
        return $this->internalBookedCount($doctorId, $date, $startTime, $availabilityId, $consultationType, $excludeAppointmentId)
            + $this->externalBookedCount($doctorId, $date, $startTime, $availabilityId, $consultationType);
    }

    public function bookedCountsDetail(
        string $doctorId,
        Carbon|string $date,
        Carbon|string $startTime,
        ?string $availabilityId = null,
        ?string $consultationType = null,
        ?string $excludeAppointmentId = null,
    ): array {
        $internal = $this->internalBookedCount($doctorId, $date, $startTime, $availabilityId, $consultationType, $excludeAppointmentId);
        $external = $this->externalBookedCount($doctorId, $date, $startTime, $availabilityId, $consultationType);

        return [
            'internal' => $internal,
            'external' => $external,
            'total' => $internal + $external,
        ];
    }

    public function availabilityBookedCount(
        DoctorAvailability $availability,
        Carbon|string|null $date = null,
        ?string $excludeAppointmentId = null,
    ): int {
        $date ??= $availability->effective_date ?? $availability->date ?? $availability->recurring_start_date;

        return $this->bookedCount(
            doctorId: $availability->doctor_id,
            date: $date,
            startTime: $availability->start_time,
            availabilityId: $availability->id,
            consultationType: $availability->consultation_type,
            excludeAppointmentId: $excludeAppointmentId,
        );
    }

    public function remainingCapacity(int $capacity, int $bookedCount): int
    {
        return max(0, $capacity - $bookedCount);
    }

    public function isFull(int $capacity, int $bookedCount): bool
    {
        return $bookedCount >= $capacity;
    }

    public function summary(
        string $doctorId,
        Carbon|string $date,
        Carbon|string $startTime,
        int $capacity,
        ?string $availabilityId = null,
        ?string $consultationType = null,
        ?string $excludeAppointmentId = null,
    ): array {
        $bookedCount = $this->bookedCount($doctorId, $date, $startTime, $availabilityId, $consultationType, $excludeAppointmentId);

        return [
            'capacity' => $capacity,
            'booked_count' => $bookedCount,
            'available_slots' => $this->remainingCapacity($capacity, $bookedCount),
            'is_full' => $this->isFull($capacity, $bookedCount),
        ];
    }

    private function internalBookedCount(
        string $doctorId,
        Carbon|string $date,
        Carbon|string $startTime,
        ?string $availabilityId,
        ?string $consultationType,
        ?string $excludeAppointmentId,
    ): int {
        return Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $this->formatDate($date))
            ->when($availabilityId, fn (Builder $query) => $query->where('availability_id', $availabilityId))
            ->when(! $availabilityId, fn (Builder $query) => $query->whereTime('appointment_time', $this->formatTime($startTime)))
            ->when($consultationType, fn (Builder $query) => $query->where('consultation_type', $consultationType))
            ->when($excludeAppointmentId, fn (Builder $query) => $query->where('id', '!=', $excludeAppointmentId))
            ->whereIn('status', [
                AppointmentStatus::CONFIRMED->value,
                AppointmentStatus::COMPLETED->value,
                AppointmentStatus::RESCHEDULED->value,
            ])
            ->where(function (Builder $query): void {
                $query->whereHas('payment', fn($paymentQuery) => $paymentQuery->where('status', \App\Enums\PaymentStatus::PAID->value))
                    ->orWhere(function (Builder $adminQuery): void {
                        $adminQuery
                            ->where('booking_source', 'admin')
                            ->where('admin_payment_type', 'without_payment');
                    });
            })
            ->count();
    }

    private function externalBookedCount(
        string $doctorId,
        Carbon|string $date,
        Carbon|string $startTime,
        ?string $availabilityId,
        ?string $consultationType,
    ): int {
        if ($availabilityId) {
            $availability = DoctorAvailability::query()
                ->select('id', 'consultation_type', 'opd_type')
                ->find($availabilityId);

            if (
                ! $availability
                || $availability->consultation_type !== 'in-person'
                || $availability->opd_type !== 'private'
            ) {
                return 0;
            }
        } elseif ($consultationType && $consultationType !== 'in-person') {
            return 0;
        }

        return ExternalBooking::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $this->formatDate($date))
            ->whereTime('start_time', $this->formatTime($startTime))
            ->where('consultation_type', 'in-person')
            ->where('opd_type', 'private')
            ->count();
    }

    private function formatDate(Carbon|string $date): string
    {
        return $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();
    }

    private function formatTime(Carbon|string $time): string
    {
        return $time instanceof Carbon ? $time->format('H:i:s') : Carbon::parse($time)->format('H:i:s');
    }
}
