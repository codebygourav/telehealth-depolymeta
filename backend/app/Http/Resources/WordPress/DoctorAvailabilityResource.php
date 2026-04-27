<?php

namespace App\Http\Resources\WordPress;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Appointment;
use Carbon\Carbon;

class DoctorAvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $now = Carbon::now();

        // Determine start time correctly
        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $this->start_time)) {
            // start_time is only H:i or H:i:s
            $slotStartTime = Carbon::parse($this->date . ' ' . $this->start_time);
        } else {
            // start_time already includes date
            $slotStartTime = Carbon::parse($this->start_time);
        }

        // Check if slot is in the past
        $isAvailable = $slotStartTime->isFuture() || $slotStartTime->isToday();

        // Count booked appointments for this slot
        $bookedCount = Appointment::where('availability_id', $this->id)
            ->where('status', '!=', 'cancelled') // exclude cancelled
            ->count();

        return [
            'id'                  => $this->id,
            'date'                => $this->date,
            'day_of_week'         => $this->day_of_week,
            'start_time'          => $this->formatTimeAmPmLocal($this->start_time),
            'end_time'            => $this->formatTimeAmPmLocal($this->end_time),
            'consultation_type'   => $this->consultation_type,
            'capacity'            => $this->capacity,
            'booked_count'        => $bookedCount,
            'available'           => $isAvailable, // mark if slot is in future or today
            'is_recurring'        => (bool) $this->is_recurring,
            'recurring_start_date' => $this->is_recurring && $this->recurring_start_date
                ? Carbon::parse($this->recurring_start_date)->format('d-m-Y')
                : null,
            'recurring_end_date'   => $this->is_recurring && $this->recurring_end_date
                ? Carbon::parse($this->recurring_end_date)->format('d-m-Y')
                : null,
        ];
    }

    private function formatTimeAmPmLocal($time)
    {
        if (!$time) return null;

        try {
            return Carbon::parse($time)->format('g:i A');
        } catch (\Exception $e) {
            return $time;
        }
    }
}
