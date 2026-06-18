<?php

namespace App\Http\Resources\Vaccination;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VaccinationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $genderRestriction = $this->gender_restriction instanceof \App\Enums\VaccinationGenderRestriction
            ? $this->gender_restriction->value
            : $this->gender_restriction;

        return [
            'id' => $this->id,
            'short_name' => $this->short_name,
            'created_at' => optional($this->created_at)?->format('d M Y, h:i A'),

            'disease_for' => $this->disease_for,
            'description' => $this->description,
            'side_effects' => $this->side_effects,
            'contraindications' => $this->contraindications,
            'prevention' => $this->contraindications,
            'precautions' => $this->precautions,
            'dosage_information' => $this->dosage_information,
            'is_multi_dose' => $this->is_multi_dose,
            'total_doses' => $this->total_doses,
            'minimum_age_days' => $this->minimum_age_days,
            'minimum_age_date' => $this->minimum_age_days !== null
                ? now()->addDays($this->minimum_age_days)->toDateString()
                : null,

            'maximum_age_days' => $this->maximum_age_days,
            'maximum_age_date' => $this->maximum_age_days !== null
                ? now()->addDays($this->maximum_age_days)->toDateString()
                : null,

            'is_active' => $this->is_active,
            'faqs' => $this->whenLoaded('faqs', function () {
                return VaccinationFaqResource::collection($this->faqs ?? collect());
            }),

        ];
    }
}
