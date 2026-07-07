<?php

namespace App\Repositories;

use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DoctorProfileRepository
{
    protected array $repeatableJsonFields = [
        'professional_experience_info',
        'education_info',
        'certifications_info',
        'awards_info',
        'fellowships_info',
    ];
    public function updateDoctorProfile(Request $request, Doctor $doctor, array $groupConfig, string $group)
    {
        $allowedFields = $groupConfig['fields'];
        $data = collect($request->all())->except('group')->toArray();

        // Handle File Uploads based on config
        if (isset($groupConfig['file_configs'])) {
            foreach ($groupConfig['file_configs'] as $fieldKey => $directory) {
                if (str_contains($fieldKey, '.*.')) {
                    // Nested field in JSON (e.g., certifications_info.*.certification_image)
                    [$parentField, $childField] = explode('.*.', $fieldKey);
                    if (isset($data[$parentField]) && is_array($data[$parentField])) {
                        foreach ($data[$parentField] as $index => &$item) {
                            $fileKey = "{$parentField}.{$index}.{$childField}";
                            $base64Field = "{$childField}_base64";

                            if ($request->hasFile($fileKey)) {
                                $file = $request->file($fileKey);
                                $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
                                $item[$childField] = $file->storeAs($directory, $filename, 'public');
                            } elseif (isset($item[$base64Field])) {
                                $path = $this->uploadBase64($item[$base64Field], $directory);
                                if ($path) {
                                    $item[$childField] = $path;
                                    unset($item[$base64Field]);
                                }
                            }
                        }
                    }
                } else {
                    // Direct field (e.g., avatar)
                    $base64Field = "{$fieldKey}_base64";
                    if ($request->hasFile($fieldKey)) {
                        $file = $request->file($fieldKey);
                        $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
                        $doctor->{$fieldKey} = $file->storeAs($directory, $filename, 'public');
                        unset($data[$fieldKey]);
                    } elseif ($request->filled($base64Field)) {
                        $path = $this->uploadBase64($request->input($base64Field), $directory);
                        if ($path) {
                            $doctor->{$fieldKey} = $path;
                            unset($data[$base64Field], $data[$fieldKey]);
                        }
                    }
                }
            }
        }

        $updateData = collect($data)->only($allowedFields)->toArray();

        // Update doctor main table
        foreach ($this->repeatableJsonFields as $field) {

            if (!isset($updateData[$field])) {
                continue;
            }

            if (!is_array($updateData[$field])) {
                continue;
            }

            $updateData[$field] = collect($updateData[$field])
                ->map(function ($item) {

                    if (!is_array($item)) {
                        return $item;
                    }

                    // Generate ID only for new items
                    if (empty($item['id'])) {
                        $item['id'] = (string) random_int(1000, 9999);
                    }

                    return $item;
                })
                ->values()
                ->toArray();
        }
        if (!empty($data['remove_item_id'])) {

            foreach ($allowedFields as $field) {

                $existing = $doctor->{$field};

                if (!is_array($existing)) {
                    continue;
                }

                $doctor->{$field} = array_values(array_filter(
                    $existing,
                    fn ($item) =>
                        ($item['id'] ?? null) !== $data['remove_item_id']
                ));
            }

            $doctor->updated_by = $request->user()->id;
            $doctor->save();

            return $this->getDoctorProfileByGroup(
                $doctor,
                $groupConfig,
                $group
            );
        }

        $doctor->fill($updateData)->save();

        if ($doctor->user) {
            $doctor->syncWithUser($updateData);
        }

        // Post-update actions for specific groups
        if ($group === 'personal_information') {

            // Sync multiple departments
            if (isset($updateData['doctor_departments'])) {
                // Accept doctor_departments as either an array of objects or a single object
                $departments = $updateData['doctor_departments'];

                // If single object, convert to array for uniform processing
                if (isset($departments['department_id'])) {
                    $departments = [$departments];
                }

                if (is_array($departments)) {
                    $syncData = [];
                    foreach ($departments as $item) {
                        if (isset($item['department_id'])) {
                            $syncData[$item['department_id']] = [
                                'id' => (string) Str::uuid(),
                                'role' => $item['role'] ?? null,
                                'order' => 1,
                            ];
                        }
                    }
                    $doctor->departments()->sync($syncData);
                }
            }
        }

        $doctor->refresh();

        return $this->getDoctorProfileByGroup($doctor, $groupConfig, $group);
    }

    public function getDoctorProfileByGroup(Doctor $doctor, array $groupConfig, string $group)
    {
        $allowedFields = $groupConfig['fields'];
        $responseData = [];

        foreach ($allowedFields as $field) {
            if ($field === 'group') continue;

            if ($field === 'avatar') {
                $responseData['avatar'] = storage_url($doctor->avatar);
            } elseif ($field === 'doctor_departments') {
                $responseData['doctor_departments'] = $doctor->departments()->get()->map(function ($dept) {
                    return [
                        'department_id' => $dept->id,
                        'department_name' => $dept->name,
                        'role' => $dept->pivot?->role,
                    ];
                })->toArray();
            } elseif ($field === 'email') {
                $responseData['email'] = $doctor->user?->email;
            } elseif ($field === 'phone') {
                $responseData['phone'] = $doctor->user?->phone;
            } elseif ($field === 'password') {
                continue;
            } else {
                $value = $doctor->{$field};

                // Automatically transform relative paths to full URLs for fields defined in file_configs
                if (isset($groupConfig['file_configs'])) {
                    foreach ($groupConfig['file_configs'] as $fieldKey => $directory) {
                        // Match direct field
                        if ($fieldKey === $field) {
                            $value = storage_url($value);
                        }
                        // Match nested field in JSON array (e.g. certifications_info.*.certification_image)
                        elseif (Str::startsWith($fieldKey, "{$field}.*.")) {
                            $childField = substr($fieldKey, strlen("{$field}.*."));
                            if (is_array($value)) {
                                foreach ($value as &$item) {
                                    $item[$childField] = isset($item[$childField]) && $item[$childField] ? storage_url($item[$childField]) : null;
                                }
                            }
                        }
                    }
                }

                $responseData[$field] = $value;
            }
        }

        return $responseData;
    }

    /**
     * Upload base64 image to storage.
     */
    protected function uploadBase64(string $base64, string $directory): ?string
    {
        if (str_contains($base64, ',')) {
            $base64 = explode(',', $base64, 2)[1];
        }

        $binary = base64_decode($base64, true);
        if ($binary === false) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_buffer($finfo, $binary);
        finfo_close($finfo);

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        $ext = $extensions[$mime] ?? 'jpg';
        $filename = (string) Str::uuid() . '.' . $ext;
        $path = $directory . '/' . $filename;

        \Illuminate\Support\Facades\Storage::disk('public')->put($path, $binary);

        return $path;
    }
}