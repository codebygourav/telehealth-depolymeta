<?php

namespace App\Http\Resources\Patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Patient\HomeAvailabilityResource;
use App\Http\Resources\Common\AppointmentResource;

class HomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            // Advertisements
            'advertisements' => $this->resource['advertisements']->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'title' => $ad->title,
                    'description' => $ad->description,
                    'image' => $ad->image
                        ? storage_url($ad->image)
                        : null,
                    'link' => $ad->link,
                ];
            }),

            'available_doctors' => HomeAvailabilityResource::collection(
                $this->resource['available_doctors']
            ),

            'patient_reviews' => $this->resource['patient_reviews'],
            'speciality_symptoms' => $this['speciality_symptoms'], // 🔥 add this
            'upcoming_appointments' => AppointmentResource::collection(
                $this->resource['upcoming_appointments'] ?? []
            ),
        ];
    }
}
