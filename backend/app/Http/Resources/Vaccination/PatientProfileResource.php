<?php

namespace App\Http\Resources\Vaccination;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profileType = $this->profile_type instanceof \App\Enums\PatientProfileType ? $this->profile_type->value : $this->profile_type;

        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'name' => $this->name,
            'profile_type' => $profileType,
            'profile_type_label' => $this->profile_type instanceof \App\Enums\PatientProfileType ? $this->profile_type->label() : ucfirst((string) $this->profile_type),
            'date_of_birth' => optional($this->date_of_birth)?->format('Y-m-d'),
            'gender' => $this->gender,
            'pregnancy_due_date' => optional($this->pregnancy_due_date)?->format('Y-m-d'),
            'blood_group' => $this->blood_group,
            'weight' => $this->weight,
            'height' => $this->height,
            'is_primary' => $this->is_primary,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
