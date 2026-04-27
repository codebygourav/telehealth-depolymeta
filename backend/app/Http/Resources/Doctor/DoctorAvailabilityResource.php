<?php

namespace App\Http\Resources\Doctor;

use App\Models\Appointment;
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

        // Count booked appointments for this slot
        $bookedCount = Appointment::where('availability_id', $this->id)
            ->whereNotIn('status', ['cancelled', 'pending']) // exclude cancelled and pending
            ->count();

        // For recurring slots, we use the date set during expansion to determine the day name
        $dateValue = $this->date ? (is_string($this->date) ? $this->date : $this->date->toDateString()) : null;

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
            'booked_count' => $bookedCount,
            ...($isAvailable ? ['available' => true] : []),
            ...($this->consultation_type === 'in-person' && $this->opd_type ? ['opd_type' => $this->opd_type] : []),
            'consultation_fee' => isset($this->consultation_fee) ? (int) round($this->consultation_fee) : null,
            'doctor_room' => $this->doctor_room,
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