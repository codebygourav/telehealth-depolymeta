<?php

namespace App\Services\OpdToken;

use App\Enums\AppointmentStatus;
use App\Models\DoctorAvailability;
use App\Services\DoctorAvailabilityService;
use App\Services\SlotCapacityService;
use Carbon\Carbon;
use BackedEnum;

class DoctorScheduleBoardService
{
    public function __construct(
        protected DisplayBoardService $displayBoardService,
        protected SlotCapacityService $slotCapacityService,
    ) {
    }

    public function buildBoard(array $display): array
    {
        $board = $this->displayBoardService->buildBoard($display);

        return array_merge($board, [
            'display_mode' => 'doctor_schedule_sidebar',
            'schedule_title' => (string) ($display['schedule_title'] ?? 'Full OPD Schedule'),
            'schedule_subtitle' => (string) ($display['schedule_subtitle'] ?? 'Morning, afternoon, and evening batches'),
            'schedule_days' => $this->buildScheduleDays($board),
        ]);
    }

    protected function buildScheduleDays(array $board): array
    {
        $doctorIds = collect($board['doctors'] ?? [])
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        $query = DoctorAvailability::query()
            ->with([
                'doctor.departments',
                'doctor.user:id,name',
                'appointments:id,availability_id,appointment_date,queue_status,status',
            ])
            ->whereHas('doctor', function ($builder): void {
                $builder->active()->withoutTestDoctors();
            })
            ->when(! empty($doctorIds), fn ($builder) => $builder->whereIn('doctor_id', $doctorIds))
            ->where(function ($builder): void {
                $builder->where('is_available', true)
                    ->orWhere('is_recurring', true)
                    ->orWhereNotNull('date');
            });

        $availabilities = $query->get();
        $availabilityService = app(DoctorAvailabilityService::class);
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weekEnd = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        $expanded = $availabilityService->expandSlotsForApi(
            $availabilities,
            $weekStart,
            $weekEnd,
            includePast: true,
            skipBlocked: true,
        );

        $days = collect();

        for ($date = $weekStart->copy(); $date->lte($weekEnd); $date->addDay()) {
            $daySlots = $expanded
                ->filter(fn (DoctorAvailability $slot): bool => Carbon::parse($slot->date)->isSameDay($date))
                ->map(fn (DoctorAvailability $slot): array => $this->mapAvailabilitySlot($slot))
                ->sortBy(fn (array $slot): string => $slot['sort_key'])
                ->values()
                ->all();

            $days->push([
                'label' => $date->format('l') . ' OPD Slots',
                'date_label' => $date->format('F d'),
                'is_today' => $date->isToday(),
                'items' => $daySlots,
            ]);
        }

        return $days->all();
    }

    protected function mapAvailabilitySlot(DoctorAvailability $slot): array
    {
        $doctorName = trim((string) ($slot->doctor?->first_name ?? '') . ' ' . (string) ($slot->doctor?->last_name ?? ''));
        $department = $slot->doctor?->departments->first()?->name ?? $slot->doctor?->qualification ?? 'General Practice';
        $startTime = $slot->start_time ? Carbon::parse($slot->start_time)->format('h:i A') : null;
        $endTime = $slot->end_time ? Carbon::parse($slot->end_time)->format('h:i A') : null;
        $timeSlot = $startTime && $endTime ? $startTime . ' - ' . $endTime : ($startTime ?: ($endTime ?: 'Time not set'));
        $bookedCount = collect($slot->appointments ?? [])
            ->filter(function ($appointment) use ($slot): bool {
                $appointmentDate = Carbon::parse($appointment->appointment_date)->toDateString();
                $slotDate = Carbon::parse($slot->date)->toDateString();
                $statusValue = $appointment->status instanceof BackedEnum
                    ? $appointment->status->value
                    : (string) ($appointment->status ?? '');

                return $appointmentDate === $slotDate
                    && strtolower($statusValue) === AppointmentStatus::CONFIRMED->value;
            })
            ->count();
        $capacity = max(1, (int) ($slot->capacity ?? 1));
        $remainingSlots = $this->slotCapacityService->remainingCapacity($capacity, $bookedCount);
        $isBusy = $this->slotCapacityService->isFull($capacity, $bookedCount);
        $isLimited = $remainingSlots > 0 && $remainingSlots < $capacity;
        $now = Carbon::now();
        $slotStart = $slot->date ? Carbon::parse($slot->date . ' ' . ($slot->start_time?->format('H:i:s') ?? '00:00:00')) : null;
        $slotEnd = $slot->date ? Carbon::parse($slot->date . ' ' . ($slot->end_time?->format('H:i:s') ?? '23:59:59')) : null;
        $isActive = $slotStart && $slotEnd ? $now->betweenIncluded($slotStart, $slotEnd) : false;

        $availabilityLabel = ! $slot->is_available
            ? 'Closed'
            : ($isBusy
                ? 'Fully Booked'
                : ($isLimited
                    ? $remainingSlots . ' Left'
                    : 'Available'));

        $availabilityClass = ! $slot->is_available
            ? 'closed'
            : ($isBusy ? 'limited' : ($isLimited ? 'limited' : 'available'));

        return [
            'doctor' => $doctorName !== '' ? $doctorName : ($slot->doctor?->user?->name ?? 'Doctor'),
            'department' => $department,
            'time_slot' => $timeSlot,
            'room' => $slot->doctor_room ?: null,
            'capacity' => $capacity,
            'booked_count' => $bookedCount,
            'remaining_slots' => $remainingSlots,
            'booking_summary' => $bookedCount . '/' . $capacity . ' booked',
            'availability_label' => $availabilityLabel,
            'availability_class' => $availabilityClass,
            'is_active' => $isActive,
            'sort_key' => $slotStart?->format('H:i:s') ?? '23:59:59',
        ];
    }
}
