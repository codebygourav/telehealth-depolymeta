<?php

namespace App\Http\Resources\WordPress;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\WordPress\DoctorAvailabilityResource;

class DoctorMinimalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isTestDoctor = (bool) $this->is_test_doctor;

        return [
            'id'          => $this->id,
            'first_name'  => $this->first_name,
            'last_name'   => $this->last_name,
            'years_experience' => $this->years_experience,
            'avatar_url' => storage_url($this->avatar),
            'slug'    => $this->slug,
            'is_test_doctor' => $isTestDoctor,
            'badge' => $isTestDoctor ? 'Test' : null,
            'badges' => $isTestDoctor ? ['Test'] : [],
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
        ];
    }
}
