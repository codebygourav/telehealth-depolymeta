<?php

namespace App\Http\Resources\Vaccination;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VaccinationModuleContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'faqs' => VaccinationFaqResource::collection($this->resource['faqs'] ?? []),
            'clinical_insight' => new VaccinationClinicalInsightResource($this->resource['clinical_insight'] ?? null),
        ];
    }
}
