<?php

namespace App\Http\Resources\Doctor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorScheduleDayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'date' => $this['date'],
            'day_name' => $this['day_name'],
            'day_short' => $this['day_short'] ?? null,
            'slots' => DoctorScheduleSlotResource::collection($this['slots']),
        ];
    }
}
