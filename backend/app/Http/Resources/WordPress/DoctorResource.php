<?php

namespace App\Http\Resources\WordPress;

use App\Http\Resources\Common\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use  App\Http\Resources\WordPress\DoctorAvailabilityResource;

class DoctorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isTestDoctor = (bool) $this->is_test_doctor;

        // Calculate ratings summary
        $totalRatings = $this->whenLoaded('reviews')
            ? $this->reviews->count()
            : ($this->reviews_count ?? $this->reviews()->count());

        $averageRating = $this->whenLoaded('reviews') && $this->reviews->count() > 0
            ? round($this->reviews->avg('rating'), 2)
            : ($this->reviews_avg_rating ?? (float) $this->reviews()->avg('rating'));

        $effectiveAvailabilities = $this->relationLoaded('availabilities')
            ? app(\App\Services\DoctorAvailabilityService::class)->expandSlotsForWordPressApi($this->resource->availabilities)
            : collect();

        // Get the availability slot with the lowest effective consultation_fee
        $lowestFeeAvailability = $effectiveAvailabilities
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
            'education_info' => $this->resolveInfoField($this->education_info),
            'awards_info' => $this->resolveInfoField($this->awards_info),
            'certifications_info' => $this->resolveInfoField($this->certifications_info),
            'fellowships_info' => $this->resolveInfoField($this->fellowships_info),
            'professional_experience_info' => $this->resolveInfoField($this->professional_experience_info),
            'special_interests' => $this->special_interests,
            'certification_entries' => $this->certification_entries,
            'availability_info' => $this->availability_info,
            'memberships_info' => $this->memberships_info,
            'bio' => $this->bio,
            'sub_title' => $this->sub_title,
            'medical_license_number' => $this->medical_license_number,
            'social_links' => $this->social_links,
            'specializations_info' => $this->specializations_info,
            'key_procedures_info' => $this->key_procedures_info,
            'expertise_info' => $this->expertise_info,
            'description' => $this->description,
            'status' => $this->status,
            'consultation_fee' => $lowestFee,
            'avatar_url' => storage_url($this->avatar),
            'is_test_doctor' => $isTestDoctor,
            'badge' => $isTestDoctor ? 'Test' : null,
            'badges' => $isTestDoctor ? ['Test'] : [],


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
                $slots = app(\App\Services\DoctorAvailabilityService::class)
                    ->expandSlotsForWordPressApi(
                        $this->availabilities->filter(function ($slot) {
                            return $slot->start_time
                                && $slot->consultation_type === 'in-person';
                        })
                    );

                return app(\App\Services\DoctorAvailabilityService::class)
                    ->formatSlotsForWordPressApi($slots);
            }),
        ];
    }

    /**
     * Resolve an info field that may be stored as free-text HTML or structured repeater data.
     *
     * When stored as free-text: [{"is_free_text": true, "html": "<p>...</p>"}]
     *   → returns the HTML string directly so WordPress can render it.
     *
     * When stored as structured repeater data: [{...}, {...}]
     *   → returns the array as-is.
     */
    private function resolveInfoField(mixed $value): mixed
    {
        if (is_array($value) && !empty($value) && isset($value[0]['is_free_text']) && $value[0]['is_free_text']) {
            return $value[0]['html'] ?? '';
        }

        return $value;
    }
}