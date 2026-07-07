<?php

namespace App\Http\Resources\Patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class HomeAvailabilityResource extends JsonResource
{
    /**
     * Transform the resource for home page - showing only TODAY's availability
     * Returns same format as AllDoctorsResource but filtered to today only
     */
    public function toArray(Request $request): array
    {
        $availabilities = app(\App\Services\DoctorAvailabilityService::class)
            ->expandSlotsForApi($this->resource->availabilities);

        // Get consultation types from effective availabilities
        $types = $availabilities->pluck('consultation_type')->filter()->unique()->values();

        // Map types to proper labels and join with " / " if multiple exist
        $typeLabels = $types->map(function ($type) {
            return $type === 'in-person' ? 'In Person' : ucfirst($type);
        })->sort();

        $consultationType = $typeLabels->isNotEmpty() ? $typeLabels->implode(' / ') : null;

        // Get lowest fee from effective availabilities
        $lowestFee = $availabilities
            ->pluck('consultation_fee')
            ->filter(fn($fee) => $fee !== null)
            ->map(fn($fee) => (float) $fee)
            ->min();

        return [
            'id' => $this->resource->user_id,
            'name' => $this->resource->first_name . ' ' . $this->resource->last_name,
            'speciality' => $this->resource->departments->pluck('name')->values(),
            'rating' => $this->average_rating ? round($this->average_rating, 1) : 0,
            'years_experience' => $this->resource->years_experience,
            'total_reviews' => $this->total_reviews ?? 0,
            'consultation_type' => $types,
            'consultation_type_label' => $consultationType,
            'languages_known' => $this->resource->languages_known,
            'consultation_fee' => $lowestFee ? round($lowestFee, 0) : 0,
            'avatar' => storage_url($this->avatar),
        ];
    }
}