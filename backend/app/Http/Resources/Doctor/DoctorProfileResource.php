<?php

namespace App\Http\Resources\Doctor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // This resource expects the $resource to be an array of profile groups
        $data = $this->resource;
        $profileGroupsConfig = config('user_profile.doctor', []);
        $formattedData = [];

        foreach ($data as $group => $values) {
            $config = $profileGroupsConfig[$group] ?? null;
            $fields = $config['fields'] ?? [];

            // If a group has only one field, flatten it so the value is directly under the group key
            if (count($fields) === 1 && isset($values[$fields[0]])) {
                $groupValue = $values[$fields[0]];
            } else {
                $groupValue = $values;
            }

            // Format date fields if the value is an array or string
            $formattedData[$group] = $this->formatDatesRecursively($groupValue);
        }

        return $formattedData;
    }

    /**
     * Recursively format date fields to d-m-Y format
     */
    protected function formatDatesRecursively($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->formatDatesRecursively($value);
            } elseif (in_array($key, ['issue_date', 'expiry_date', 'start_date', 'end_date', 'date_of_birth']) && !empty($value)) {
                try {
                    $data[$key] = \Carbon\Carbon::parse($value)->format('d-m-Y');
                } catch (\Exception $e) {
                    // Keep original value if parsing fails
                }
            }
        }

        return $data;
    }
}
