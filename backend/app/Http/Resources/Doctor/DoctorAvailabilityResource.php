<?php

namespace App\Http\Resources\Doctor;

use App\Services\SlotCapacityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorAvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $now = Carbon::now();

        // Determine start time correctly
        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $this->start_time)) {
            // start_time is only H:i or H:i:s
            $slotStartTime = Carbon::parse(($this->date ?? $this->recurring_start_date) . ' ' . $this->start_time);
        } else {
            // start_time already includes date or is recurring pattern
            $slotStartTime = Carbon::parse($this->start_time);
        }

        // Check if slot is in the past
        $isAvailable = $slotStartTime->isFuture() || $slotStartTime->isToday();

        // For recurring slots, we use the date set during expansion to determine the day name
        $dateValue = $this->date ? (is_string($this->date) ? $this->date : $this->date->toDateString()) : null;

        $capacitySummary = $dateValue
            ? app(SlotCapacityService::class)->summary(
                doctorId: $this->doctor_id,
                date: $dateValue,
                startTime: $this->start_time,
                capacity: (int) ($this->capacity ?? 1),
                availabilityId: $this->id,
                consultationType: $this->consultation_type,
            )
            : [
                'booked_count' => 0,
                'available_slots' => (int) ($this->capacity ?? 1),
                'is_full' => false,
            ];

        $dayName = null;
        if ($dateValue) {
            $dayName = strtolower(Carbon::parse($dateValue)->format('l'));
        } elseif ($this->day_of_week) {
            $dayName = strtolower($this->day_of_week);
        } elseif ($this->recurring_start_date) {
            $dayName = strtolower(Carbon::parse($this->recurring_start_date)->format('l'));
        }

        return [
            'id' => $this->id,
            'availability_override_id' => $this->override_id ?? null,
            'date' => $dateValue,
            'day_of_week' => $dayName,
            'booking_start_time' => $this->start_time ? Carbon::parse($this->start_time)->format('H:i:s') : null,
            'start_time' => $this->formatTimeAmPmLocal($this->start_time),
            'end_time' => $this->formatTimeAmPmLocal($this->end_time),
            'consultation_type' => $this->consultation_type,
            'consultation_type_label' => $this->consultation_type === 'video'
                ? 'Video'
                : 'Clinic Visit',
            'capacity' => $this->capacity,
            'booked_count' => $capacitySummary['booked_count'],
            'source' => $this->source ?? (($this->override_id ?? null) ? 'override' : ((bool) $this->is_recurring ? 'recurring' : 'availability')),
            ...($isAvailable && ! $capacitySummary['is_full'] ? ['available' => true] : []),
            ...($this->consultation_type === 'in-person' && $this->opd_type ? ['opd_type' => $this->opd_type] : []),
            'consultation_fee' => isset($this->consultation_fee) ? (int) round($this->consultation_fee) : null,
            'doctor_room' => $this->doctor_room,
            'available_slots' => $capacitySummary['available_slots'],
            'is_full' => $capacitySummary['is_full'],
            'recurring_start_date' => $this->is_recurring && $this->recurring_start_date
                ? Carbon::parse($this->recurring_start_date)->format('d-m-Y')
                : null,
            'recurring_end_date' => $this->is_recurring && $this->recurring_end_date
                ? Carbon::parse($this->recurring_end_date)->format('d-m-Y')
                : null,
        ];
    }

    private function formatTimeAmPmLocal($time)
    {
        if (! $time) {
            return null;
        }

        try {
            return Carbon::parse($time)->format('g:i A');
        } catch (\Exception $e) {
            return $time;
        }
    }
}
