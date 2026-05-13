<?php

namespace App\Http\Resources\Vaccination;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VaccinationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'manufacturer' => $this->manufacturer,
            'disease_for' => $this->disease_for,
            'description' => $this->description,
            'side_effects' => $this->side_effects,
            'contraindications' => $this->contraindications,
            'precautions' => $this->precautions,
            'dosage_information' => $this->dosage_information,
            'is_multi_dose' => $this->is_multi_dose,
            'total_doses' => $this->total_doses,
            'is_active' => $this->is_active,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
