<?php

namespace App\Http\Resources\Common;

use App\Services\WherebyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoConsultationResource extends JsonResource
{
    protected string $userType;

    public function __construct($resource, string $userType = 'patient')
    {
        parent::__construct($resource);
        $this->userType = $userType;
    }

    public function toArray(Request $request): array
    {
        $wherebyService = app(WherebyService::class);
        $canStart = $wherebyService->canStartConsultation($this->resource);

        // Get display name based on user type
        $displayName = $this->getDisplayName();

        // Get appropriate join URL based on user type
        $joinUrl = $wherebyService->getJoinUrl($this->resource, $this->userType, $displayName);

        return [
            'id' => $this->id,
            'room_id' => $this->room_id,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),

            // URLs - show appropriate URL based on user type
            'join_url' => $joinUrl,
            'room_url' => $this->room_url,

            // Only show host URL to doctors
            'host_url' => $this->when($this->userType === 'doctor', $this->host_url),

            // Show participate URL to patients
            'participate_url' => $this->when($this->userType === 'patient', $this->participate_url),

            // Permissions & Status
            'can_join' => $canStart['can_start'],
            'join_message' => $canStart['reason'],
            'user_type' => $this->userType,
            'display_name' => $displayName,

            // Timestamps
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'duration_minutes' => $this->getDurationInMinutes(),
            'created_at' => $this->created_at?->toIso8601String(),

            // Related data
            'appointment' => $this->when($this->relationLoaded('appointment'), function () {
                return [
                    'id' => $this->appointment->id,
                    'slug' => $this->appointment->slug,
                    'date' => $this->appointment->appointment_date,
                    'time' => $this->appointment->appointment_time,
                    'status' => $this->appointment->status,
                ];
            }),

            'patient' => $this->when($this->relationLoaded('patient') && $this->patient, function () {
                return [
                    'id' => $this->patient->id,
                    'name' => $this->patient->first_name . ' ' . $this->patient->last_name,
                    'avatar' => storage_url($this->patient->user?->avatar),
                ];
            }),

            'doctor' => $this->when($this->relationLoaded('doctor') && $this->doctor, function () {
                return [
                    'id' => $this->doctor->id,
                    'name' => $this->doctor->first_name . ' ' . $this->doctor->last_name,
                    'avatar' => storage_url($this->doctor->avatar),
                    'specialty' => $this->doctor->departments->first()?->name ?? null,
                ];
            }),
        ];
    }

    /**
     * Get display name for the user
     */
    protected function getDisplayName(): string
    {
        if ($this->userType === 'doctor' && $this->doctor) {
            return $this->doctor->first_name . ' ' . $this->doctor->last_name;
        }

        if ($this->userType === 'patient' && $this->patient) {
            return $this->patient->first_name . ' ' . $this->patient->last_name;
        }

        return 'User';
    }

    /**
     * Get human-readable status label
     */
    protected function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Waiting to Start',
            'active' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status ?? 'Unknown'),
        };
    }
}