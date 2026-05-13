<?php

namespace App\Http\Resources\Vaccination;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientVaccinationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof \App\Enums\VaccinationStatus ? $this->status->value : $this->status;

        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'patient' => $this->whenLoaded('patient', fn () => [
                'id' => $this->patient?->id,
                'name' => trim((string) ($this->patient?->first_name . ' ' . $this->patient?->last_name)),
                'email' => $this->patient?->email ?? $this->patient?->user?->email,
                'phone' => $this->patient?->mobile_no ?? $this->patient?->user?->phone,
            ]),
            'doctor_id' => $this->doctor_id,
            'vaccination_id' => $this->vaccination_id,
            'vaccination_template_id' => $this->vaccination_template_id,
            'set_name' => $this->set_name,
            'set_sort_order' => $this->set_sort_order,
            'recommended_age_label' => $this->recommended_age_label,
            'status' => $status,
            'status_label' => $this->status instanceof \App\Enums\VaccinationStatus ? $this->status->label() : ucfirst((string) $this->status),
            'dose_no' => $this->dose_no,
            'first_dose_date' => optional($this->first_dose_date)?->format('Y-m-d'),
            'due_after_days' => $this->due_after_days,
            'due_after_months' => $this->due_after_months,
            'scheduled_date' => optional($this->scheduled_date)?->format('Y-m-d'),
            'completed_date' => optional($this->completed_date)?->format('Y-m-d'),
            'batch_number' => $this->batch_number,
            'manufacturer' => $this->manufacturer,
            'given_at' => $this->given_at,
            'given_by' => $this->given_by,
            'doctor_notes' => $this->doctor_notes,
            'side_effect_observed' => $this->side_effect_observed,
            'patient_reaction' => $this->patient_reaction,
            'reminder_sent' => $this->reminder_sent,
            'vaccination' => [
                'id' => $this->vaccination?->id,
                'name' => $this->vaccination?->name,
                'short_name' => $this->vaccination?->short_name,
                'manufacturer' => $this->vaccination?->manufacturer,
                'disease_for' => $this->vaccination?->disease_for,
                'description' => $this->vaccination?->description,
                'side_effects' => $this->vaccination?->side_effects,
                'contraindications' => $this->vaccination?->contraindications,
                'precautions' => $this->vaccination?->precautions,
                'dosage_information' => $this->vaccination?->dosage_information,
                'is_multi_dose' => $this->vaccination?->is_multi_dose,
                'total_doses' => $this->vaccination?->total_doses,
            ],
            'template' => $this->whenLoaded('template', fn () => [
                'id' => $this->template?->id,
                'name' => $this->template?->name,
            ]),
            'documents' => $this->whenLoaded('documents', fn () => $this->documents->map(fn ($document) => [
                'id' => $document->id,
                'document' => $document->document,
                'certificate_number' => $document->certificate_number,
            ])),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
