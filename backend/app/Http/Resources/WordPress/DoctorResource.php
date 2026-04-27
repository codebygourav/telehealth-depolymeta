<?php

namespace App\Http\Resources\WordPress;

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
            'avatar_url' => $this->user->avatar ?? null,
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
                        return $slot->start_time
                            && $slot->is_available
                            && $slot->consultation_type === 'in-person';
                    })
                );
            }),
        ];
    }
}
