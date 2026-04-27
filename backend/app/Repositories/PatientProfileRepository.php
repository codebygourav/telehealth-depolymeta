<?php

namespace App\Repositories;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\ApiResponseService;

class PatientProfileRepository
{
    /**
     * Update patient profile based on group configuration.
     */
    public function updatePatientProfile(Request $request, Patient $patient, array $groupConfig, string $group)
    {
        $allowedFields = $groupConfig['fields'] ?? [];
        $data = $request->all();

        // For requests that might wrap data in a 'data' key (consistent with some frontend patterns)
        if ($request->has('data')) {
            $data = $request->input('data');
        } else {
            unset($data['group']);
        }

        // Handle Avatar/Files if any configured
        if (in_array('avatar', $allowedFields)) {
            $patient->handleAvatarUpload($request);
            unset($data['avatar'], $data['avatar_base64']);
        }

        // Filter data to only allowed fields
        $updateData = collect($data)->only($allowedFields)->toArray();
        $updateData['updated_by'] = $request->user()->id;

        // Perform main update
        $patient->fill($updateData)->save();

        if ($patient->user) {
            $patient->syncWithUser($updateData);
        }

        $patient->refresh();

        return $this->getPatientProfileByGroup($patient, $groupConfig, $group);
    }

    /**
     * Get patient profile data based on group configuration.
     */
    public function getPatientProfileByGroup(Patient $patient, array $groupConfig, string $group)
    {
        $fields = $groupConfig['fields'] ?? [];
        $data = [];

        foreach ($fields as $field) {
            if ($field === 'avatar') {
                $data['avatar'] = storage_url($patient->avatar);
                $data['avatar_path'] = $patient->avatar;
            } else {
                $data[$field] = $patient->getAttribute($field);
            }
        }

        return $data;
    }
}
