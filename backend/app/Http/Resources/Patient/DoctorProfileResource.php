<?php

namespace App\Http\Resources\Patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Doctor\DoctorAvailabilityResource;
use Carbon\Carbon;
use App\Http\Resources\Reviews\DoctorReviewResource;

class DoctorProfileResource extends JsonResource
{
    protected $averageRating;
    protected $totalReviews;

    public function __construct($resource, $averageRating = null, $totalReviews = null)
    {
        parent::__construct($resource);

        $this->averageRating = $averageRating;
        $this->totalReviews = $totalReviews;
    }

    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'user_id' => $this->user_id,

            'profile' => [
                'name' => $this->first_name . ' ' . $this->last_name,
                'avatar' => storage_url($this->avatar),
                'department' => optional($this->departments->first())->name,
                'years_experience' => $this->years_experience,
            ],

            'about' => [
                'bio' => $this->bio,
                'description' => $this->description,
            ],

            'education' => collect($this->education_info)
                ->filter(function ($edu) {
                    return (
                        !empty($edu['degree']) && $edu['degree'] !== '' &&
                        !empty($edu['institution']) && $edu['institution'] !== ''
                    );
                })
                ->map(function ($edu) {
                    return [
                        'degree' => $edu['degree'] ?? null,
                        'institution' => $edu['institution'] ?? null,
                        'completion_year' => $edu['completion_year'] ?? null,
                    ];
                })
                ->values(),

            'languages' => $this->languages_known ?? [],

            'appointment_types' => [
                'in_person' => $this->availabilities->contains(function ($slot) {
                    return $slot->consultation_type === 'in-person';
                }),
                'video' => $this->availabilities->contains(function ($slot) {
                    return $slot->consultation_type === 'video';
                }),
            ],

            'doctor_reviews' => DoctorReviewResource::collection(
                $this->whenLoaded('reviews')
            ),

            'availability' => (function () use ($request) {
                $service = app(\App\Services\DoctorAvailabilityService::class);
                $slots = $service->expandSlots(
                    $this->availabilities,
                    Carbon::today(),
                    Carbon::today()->addMonths(1)

                );
                return $service->groupSlotsByDate($slots)->map(fn($group) => [
                    'date' => $group['date'],
                    'slots' => DoctorAvailabilityResource::collection($group['slots'])->resolve($request),
                ]);
            })(),
            'status' => $this->status instanceof \App\Enums\DoctorStatus
                ? $this->status->name
                : (\App\Enums\DoctorStatus::tryFrom($this->status)?->name ?? ucfirst($this->status)),

            'review_summary' => [
                'average_rating' => $this->averageRating,
                'total_reviews' => $this->totalReviews,
            ],
        ];
    }
}