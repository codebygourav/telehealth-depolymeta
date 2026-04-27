<?php

namespace App\Http\Resources\Doctor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $filter = $request->get('filter', 'day');

        if ($filter === 'day') {
            return [
                'date' => $this['date'],
                'day_name' => $this['day_name'],
                'slots' => DoctorScheduleSlotResource::collection($this['slots']),
            ];
        }

        return [
            'start_date' => $this['start_date'],
            'end_date' => $this['end_date'],
            'days' => DoctorScheduleDayResource::collection($this['days']),
        ];
    }
}
