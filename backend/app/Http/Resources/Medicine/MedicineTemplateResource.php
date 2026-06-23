<?php

namespace App\Http\Resources\Medicine;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicineTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scope_type' => $this->scope_type ?? ($this->doctor_id ? 'doctor' : 'global'),
            'department_id' => $this->department_id,
            'doctor_id' => $this->doctor_id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(fn ($item) => [
                    'id' => $item->id,
                    'medicine_id' => $item->medicine_id,
                    'medicine_name' => $item->medicine_name,
                    'medicine_type' => $item->medicine_type,
                    'dosage' => $item->dosage,
                    'doses_per_day' => $item->doses_per_day,
                    'first_dose_time' => $item->first_dose_time,
                    'dose_interval_hours' => $item->dose_interval_hours,
                    'frequency' => $item->frequency,
                    'frequency_times' => $item->frequency_times ?? [],
                    'meal_timing' => $item->meal_timing,
                    'duration_type' => $item->duration_type,
                    'duration_value' => $item->duration_value,
                    'instructions' => $item->instructions,
                    'sort_order' => $item->sort_order,
                    'use_type' => $item->use_type ?? 'regular',
                    'take_when' => $item->take_when,
                    'min_gap' => $item->min_gap,
                    'max_doses_per_day' => $item->max_doses_per_day,
                    'patient_instruction' => $item->patient_instruction,
                    'medicine' => [
                        'id' => $item->medicine?->id,
                        'name' => $item->medicine?->name,
                        'type' => $item->medicine?->type?->name,
                    ],
                ]);
            }),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
