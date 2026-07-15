<?php

namespace App\Http\Resources\Doctor;

use App\Http\Resources\Reviews\DoctorReviewResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GetProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Doctor $doctor */
        $doctor = $this->resource;

        return [
            'personal_information' => [
                'first_name' => $doctor->first_name,
                'last_name' => $doctor->last_name,
                'bio' => $doctor->bio,
                'doctor_departments' => $doctor->departments->map(function ($dept) {
                    return [
                        'department_id' => $dept->id,
                        'department_name' => $dept->name,
                        'role' => $dept->pivot?->role,
                    ];
                }),
                'email' => $doctor->user?->email,
                'avatar' => storage_url($doctor->avatar),
            ],
            'working_experience' => $this->formatDatesRecursively($doctor->professional_experience_info ?? []),
            'education_info' => $this->formatDatesRecursively($doctor->education_info ?? []),
            'certifications_info' => $this->formatDatesRecursively($this->mapFileUrls($doctor->certifications_info ?? [], 'certification_image')),
            'address' => [
                'address_line1' => $doctor->address_line1,
                'address_line2' => $doctor->address_line2,
                'country' => $doctor->country,
                'state' => $doctor->state,
                'area' => $doctor->area,
                'city' => $doctor->city,
                'pincode' => $doctor->pincode,
                'landmark' => $doctor->landmark,
            ],
            'awards_info' => $this->formatDatesRecursively($this->mapFileUrls($doctor->awards_info ?? [], 'award_image')),
            'fellowships_training' => $this->formatDatesRecursively($doctor->fellowships_info ?? []),
            'additional_information' => [
                'special_interests' => $doctor->special_interests,
                'availability_info' => $doctor->availability_info,
                'memberships_info' => $doctor->memberships_info,
                'specializations_info' => $doctor->specializations_info,
                'key_procedures_info' => $doctor->key_procedures_info,
                'expertise_info' => $doctor->expertise_info,
            ],
            'social_media' => [
                'facebook' => $doctor->social_links['facebook'] ?? null,
                'twitter' => $doctor->social_links['twitter'] ?? null,
                'linkedin' => $doctor->social_links['linkedin'] ?? null,
                'instagram' => $doctor->social_links['instagram'] ?? null,
                'website' => $doctor->social_links['website'] ?? null,
            ],
            'medical_license' => $doctor->medical_license_number,
            'reviews' => DoctorReviewResource::collection($this->getLatestReviews($doctor)),
            'review_summary' => [
                'average_rating' => round($doctor->reviews()->avg('rating') ?? 0, 1),
                'total_reviews' => (int) $doctor->reviews()->count(),
            ],
            'voice_settings' => [
                'voice_name' => $doctor->voice_name,
                'speech_rate' => $doctor->speech_rate ?? 1.0,
                'speech_pitch' => $doctor->speech_pitch ?? 1.0,
                'speech_locale' => $doctor->speech_locale ?? 'en-IN',
            ],
            'ai_training' => $this->normalizeAiTrainingProfile($doctor->ai_training_profile),
        ];
    }

    protected function normalizeAiTrainingProfile(?array $profile): array
    {
        $profile = $profile ?? [];

        return [
            'pronunciation_dictionary' => array_values($profile['pronunciation_dictionary'] ?? []),
            'speech_word_corrections' => array_values($profile['speech_word_corrections'] ?? []),
            'medicine_shortcuts' => array_values($profile['medicine_shortcuts'] ?? []),
            'common_diagnoses' => array_values($profile['common_diagnoses'] ?? []),
            'frequently_used_instructions' => array_values($profile['frequently_used_instructions'] ?? []),
            'procedures_investigations' => array_values($profile['procedures_investigations'] ?? []),
        ];
    }

    protected function getLatestReviews($doctor)
    {
        return $doctor->reviews()
            ->with(['patient', 'doctor.user', 'doctor.departments', 'fakerPatient'])
            ->latest()
            ->take(5)
            ->get();
    }

    protected function mapFileUrls(array $data, string $field)
    {
        foreach ($data as &$item) {
            if (isset($item[$field]) && $item[$field]) {
                $item[$field] = storage_url($item[$field]);
            }
        }
        return $data;
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
                    $data[$key] = Carbon::parse($value)->format('d-m-Y');
                } catch (\Exception $e) {
                    // Keep original value if parsing fails
                }
            }
        }

        return $data;
    }
}
