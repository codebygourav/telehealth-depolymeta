<?php

namespace App\Http\Resources\Doctor;

use App\Http\Resources\Common\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
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
            'awards_info' => format_repeater_with_images(
                $this->resource,
                $this->awards_info,
                'award',
                'award_image',
                'award_image_url'
            ),
            'certifications_info' => format_repeater_with_images(
                $this->resource,
                $this->certifications_info,
                'certification',
                'certification_image',
                'certification_image_url'
            ),
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
            'avatar_url' => storage_url($this->user->avatar),
            'departments' => $this->departments->map(function ($department) {
                return [
                    'name' => $department->name,
                    'role' => $department->pivot->role,
                    'order' => $department->pivot->order,
                ];
            })->toArray(),
            // Availability
            'availabilities' => $this->whenLoaded('availabilities', function () {
                $slots = app(\App\Services\DoctorAvailabilityService::class)
                    ->expandSlotsForApi($this->availabilities->filter(fn ($slot) => $slot->start_time));

                return DoctorAvailabilityResource::collection(
                    $slots
                );
            }),
        ];
    }
}
