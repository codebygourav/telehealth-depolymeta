<?php

namespace App\Http\Resources\Vaccination;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VaccinationTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,
            'doctor_id' => $this->doctor_id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {

                    return [
                        'id' => $item->id,
                        'vaccination_id' => $item->vaccination_id,
                        'set_name' => $item->set_name,
                        'set_description' => $item->set_description,
                        'set_sort_order' => $item->set_sort_order,
                        'dose_no' => $item->dose_no,
                        'recommended_age_label' => $item->recommended_age_label,
                        'due_after_days' => $item->due_after_days,
                        'due_after_months' => $item->due_after_months,
                        'sort_order' => $item->sort_order,
                        'vaccination' => [
                            'id' => $item->vaccination?->id,
                            'name' => $item->vaccination?->name,
                            'short_name' => $item->vaccination?->short_name,
                            'manufacturer' => $item->vaccination?->manufacturer,
                        ]
                    ];
                });
            }),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
