<?php

namespace App\Http\Resources\Doctor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorScheduleSlotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'],
            'appointment_id' => $this['appointment_id'] ?? null,
            'date' => $this['date'],
            'day_name' => $this['day_name'],
            'start_time' => $this['start_time'],
            'end_time' => $this['end_time'],
            'time_range' => $this['time_range'],
            'consultation_type' => $this['consultation_type'],
            'consultation_type_label' => $this['consultation_type_label'],
            'capacity' => $this['capacity'] ?? 1,
            'slot_capacity' => $this['slot_capacity'] ?? 1,
            'booked_count' => $this['booked_count'] ?? 0,
            'available_slots' => $this['available_slots'] ?? 0,
            'is_full' => $this['is_full'] ?? false,
            'is_recurring' => $this['is_recurring'] ?? false,
            'doctor_room' => $this['doctor_room'] ?? null,
            'is_available' => $this['is_available'] ?? true,
            'appointments' => $this['appointments'] ?? [],
            'external_bookings' => $this['external_bookings'] ?? [],
        ];
    }
}
