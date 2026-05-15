<?php

namespace App\Http\Resources\WordPress;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\WordPress\DoctorAvailabilityResource;

class DoctorMinimalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'first_name'  => $this->first_name,
            'last_name'   => $this->last_name,
            'years_experience' => $this->years_experience,
            'avatar_url'  => storage_url($this->user->avatar),
            'slug'    => $this->slug,
            'education_info' => $this->education_info,
            'languages_known' => $this->languages_known,
            'departments' => $this->whenLoaded('departments', function () {
                return $this->departments->map(function ($department) {
                    return [
                        'name'  => $department->name,
                        'role'  => $department->pivot->role,
                        'order' => $department->pivot->order,
                    ];
                })->toArray();
            }),
            // Availability - only in-person
            'availabilities' => $this->whenLoaded('availabilities', function () {
                return DoctorAvailabilityResource::collection(
                    $this->availabilities->filter(fn($slot) => $slot->start_time)
                );
            }),
        ];
    }
}
