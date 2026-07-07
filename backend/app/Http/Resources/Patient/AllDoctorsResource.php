<?php

namespace App\Http\Resources\Patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Symptom;

class AllDoctorsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $availabilities = app(\App\Services\DoctorAvailabilityService::class)
            ->expandSlotsForApi($this->resource->availabilities);

        $types = $availabilities
            ->where('is_available', true)
            ->pluck('consultation_type')
            ->filter()
            ->unique()
            ->values();

        $consultationType = null;
        if ($types->count() === 1) {
            $consultationType = $types->first();
        } elseif ($types->contains('in-person') && $types->contains('video')) {
            $consultationType = 'both';
        } elseif ($types->count() > 1) {
            $consultationType = $types->all();
        }

        $consultationTypeLabel = null;
        if ($types->count() === 1) {
            $consultationTypeLabel = $types->first() === 'in-person' ? 'In person' : 'Video';
        } elseif ($types->contains('in-person') && $types->contains('video')) {
            $consultationTypeLabel = 'In person/ Video';
        } elseif ($types->count() > 1) {
            $consultationTypeLabel = $types->map(fn($t) => $t === 'in-person' ? 'In person' : 'Video')->implode('/ ');
        }

        $lowestFee = $availabilities
            ->where('is_available', true)
            ->pluck('consultation_fee')
            ->filter(fn($fee) => $fee !== null)
            ->map(fn($fee) => (float) $fee)
            ->min();

        return [
            'id' => $this->resource->user_id, // Keeping as user_id as per current implementation
            'name' => $this->resource->first_name . ' ' . $this->resource->last_name,
            'speciality' => $this->departments->map(function ($department) {
                $symptomNames = [];
                $symptomIds = $department->symptom_ids ?: [];
                
                if (isset($this->symptomsMap)) {
                    foreach ($symptomIds as $id) {
                        if ($symptom = $this->symptomsMap->get($id)) {
                            $symptomNames[] = $symptom->name;
                        }
                    }
                } else {
                    $symptomNames = Symptom::whereIn('id', $symptomIds)->pluck('name')->toArray();
                }

                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'symptoms' => $symptomNames,
                ];
            })->values(),
            'rating' => $this->average_rating ? round((float)$this->average_rating, 1) : 0,
            'total_reviews' => (int)($this->total_reviews ?? 0),
            'years_experience' => $this->resource->years_experience,
            'languages_known' => $this->resource->languages_known,
            'consultation_type' => $consultationType,
            'consultation_type_label' => $consultationTypeLabel,
            'consultation_fee' => $lowestFee ? (float)$lowestFee : null,
            'avatar' => storage_url($this->avatar),
        ];
    }
}