<?php

namespace App\Http\Resources\Patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Symptom;

class SpecialityAndSymptomsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Get department's featured image using the accessor
        $departmentIcon = storage_url($this->department_featured);

        // Fetch related symptoms efficiently
        $symptomIds = is_array($this->symptom_ids) ? $this->symptom_ids : [];
        
        // Use pre-loaded symptomsMap if available, otherwise fetch
        if (isset($this->symptomsMap)) {
            $symptomsData = collect($symptomIds)->map(function($id) {
                $symptom = $this->symptomsMap->get($id);
                return $symptom ? [
                    'name' => $symptom->name,
                    'icon' => storage_url($symptom->featured_image),
                ] : null;
            })->filter()->values();
        } else {
            $symptoms = !empty($symptomIds)
                ? Symptom::whereIn('id', $symptomIds)->get()
                : collect();

            $symptomsData = $symptoms->map(function ($symptom) {
                return [
                    'name' => $symptom->name,
                    'icon' => storage_url($symptom->featured_image),
                ];
            })->values();
        }

        return [
            'id' => $this->id,
            'department' => [
                'name' => $this->name,
                'icon' => $departmentIcon,
                'stamp' => storage_url($this->department_stamp),
            ],
            'symptoms' => $symptomsData,
        ];
    }
}
