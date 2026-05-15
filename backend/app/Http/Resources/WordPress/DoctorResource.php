<?php

namespace App\Http\Resources\WordPress;

use App\Http\Resources\Common\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Calculate ratings summary
        $totalRatings = $this->whenLoaded('reviews')
            ? $this->reviews->count()
            : ($this->reviews_count ?? $this->reviews()->count());

        $averageRating = $this->whenLoaded('reviews') && $this->reviews->count() > 0
            ? round($this->reviews->avg('rating'), 2)
            : ($this->reviews_avg_rating ?? (float) $this->reviews()->avg('rating'));

        // Get the availability slot with the lowest consultation_fee
        $lowestFeeAvailability = $this->resource->availabilities
            ->filter(fn($slot) => $slot->consultation_fee !== null)
            ->sortBy(fn($slot) => (float) $slot->consultation_fee)
            ->first();

        $lowestFee = $lowestFeeAvailability
            ? (float) $lowestFeeAvailability->consultation_fee
            : null;

        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'age' => $this->age,
            'gender' => $this->gender,
            'years_experience' => $this->years_experience,
            'languages_known' => $this->languages_known,
            'education_info' => $this->education_info,
            'awards_info' => $this->awards_info,
            'certifications_info' => $this->certifications_info,
            'fellowships_info' => $this->fellowships_info,
            'professional_experience_info' => $this->professional_experience_info,
            'special_interests' => $this->special_interests,
            'certification_entries' => $this->certification_entries,
            'availability_info' => $this->availability_info,
            'memberships_info' => $this->memberships_info,
            'bio' => $this->bio,
            'medical_license_number' => $this->medical_license_number,
            'social_links' => $this->social_links,
            'specializations_info' => $this->specializations_info,
            'key_procedures_info' => $this->key_procedures_info,
            'expertise_info' => $this->expertise_info,
            'description' => $this->description,
            'status' => $this->status,
            'consultation_fee' => $lowestFee,
            'avatar_url' => $this->user->avatar ?? null,
            'average_rating' => $averageRating ? round($averageRating, 2) : null,
            'total_rating' => $totalRatings,
            'departments' => $this->whenLoaded('departments', function () {
                return $this->departments->map(function ($department) {
                    return [
                        'name' => $department->name,
                        'role' => $department->pivot->role,
                        'order' => $department->pivot->order,
                    ];
                })->toArray();
            }),
            // Availability - only in-person and available
            'availabilities' => $this->whenLoaded('availabilities', function () {
                return DoctorAvailabilityResource::collection(
                    $this->availabilities->filter(function ($slot) {
                        // Only include slots with a valid start_time, are available,
                        // and are for in-person consultation
                        return $slot->start_time
                            && $slot->is_available
                            && $slot->consultation_type === 'in-person';
                    })->values() // Re-index to avoid sparse arrays
                );
            }),
        ];
    }
}
