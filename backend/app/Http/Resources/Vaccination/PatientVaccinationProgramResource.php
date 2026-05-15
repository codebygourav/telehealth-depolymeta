<?php

namespace App\Http\Resources\Vaccination;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientVaccinationProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof \App\Enums\PatientVaccinationProgramStatus ? $this->status->value : $this->status;

        return [
            'id' => $this->id,
            'patient_profile_id' => $this->patient_profile_id,
            'vaccination_program_id' => $this->vaccination_program_id,
            'vaccination_template_id' => $this->vaccination_template_id,
            'doctor_id' => $this->doctor_id,
            'start_date' => optional($this->start_date)?->format('Y-m-d'),
            'status' => $status,
            'status_label' => $this->status instanceof \App\Enums\PatientVaccinationProgramStatus ? $this->status->label() : ucfirst((string) $this->status),
            'patient_profile' => $this->whenLoaded('patientProfile', fn () => new PatientProfileResource($this->patientProfile)),
            'program' => $this->whenLoaded('vaccinationProgram', fn () => new VaccinationProgramResource($this->vaccinationProgram)),
            'template' => $this->whenLoaded('vaccinationTemplate', fn () => new VaccinationTemplateResource($this->vaccinationTemplate)),
            // If vaccinations not showing, the relation might not be loaded. Return empty array if not loaded.
            'vaccinations' => $this->relationLoaded('patientVaccinations')
                ? PatientVaccinationResource::collection($this->patientVaccinations)
                : [],

            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
