<?php

namespace App\Http\Resources\Reviews;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\FakerPatient;

class DoctorReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Patient Age & Location
        if ($this->review_type === 'fake') {
            $faker = $this->fakerPatient ?? null;
            $patientAge = $faker?->age ?? 30;
            $patientLocation = $faker?->address ?? null;
        } else {
            $patientAge = $this->patient?->date_of_birth
                ? Carbon::parse($this->patient->date_of_birth)->age
                : null;
            $patientLocation = $this->patient?->address;
        }

        return [
            'id' => $this->id,
            'patient_name' => $this->patient_name,
            'patient_image' => storage_url($this->patient_image),
            'patient_age' => $patientAge . ' Years',
            'patient_location' => $patientLocation,
            'title' => $this->title,
            'content' => $this->content,
            'rating' => $this->rating,
            'total_reviews' => $this->doctor && $this->doctor->reviews ? $this->doctor->reviews->count() : 0,
            'doctor_name' => $this->doctor?->user?->name,
            'doctor_avatar' => $this->doctor?->avatar ? storage_url($this->doctor->avatar) : null,
            'doctor_experience' => $this->doctor?->years_experience ? $this->doctor->years_experience . ' Years' : null,
            'doctor_departments' => $this->doctor && $this->doctor->departments
                ? implode(', ', $this->doctor->departments->pluck('name')->filter()->values()->all())
                : '',

            'rating_stars' => str_repeat('⭐', (int) $this->rating),
            'created_at' => $this->created_at->diffForHumans(),
        ];
    }
}
