<?php

namespace App\Http\Resources\Common;

use App\Enums\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $now = Carbon::now();

        // Build appointment start datetime robustly
        $dateStr = ($this->appointment_date instanceof Carbon)
            ? $this->appointment_date->format('Y-m-d')
            : $this->appointment_date;

        $appointmentDateTime = Carbon::parse($dateStr . ' ' . $this->appointment_time);

        // Build appointment end datetime safely
        if ($this->availability && $this->availability->end_time) {
            $endTime = $this->availability->end_time;

            // If end_time already has date, parse directly, otherwise append dateStr
            $appointmentEndDateTime = str_contains($endTime, '-')
                ? Carbon::parse($endTime)
                : Carbon::parse($dateStr . ' ' . $endTime);
        } else {
            $appointmentEndDateTime = $appointmentDateTime->copy()->addHour();
        }

        // Call Now window: 1 hour before start → appointment end
        $callWindowStart = $appointmentDateTime->copy()->subHour();
        $callWindowEnd = $appointmentEndDateTime;

        $callNow = false;
        $canStartVideoCall = AppointmentStatus::equals($this->status, AppointmentStatus::CONFIRMED) ||
            AppointmentStatus::equals($this->status, AppointmentStatus::RESCHEDULED);

        // Requirement: Must be today AND within the window
        if (
            ($this->consultation_type === 'video' || $this->consultation_type === 'Video') &&
            $now->isSameDay($appointmentDateTime) &&
            $canStartVideoCall
        ) {
            $callNow = $now->between($callWindowStart, $callWindowEnd);
        }

        // Use Enum label directly
        $statusLabel = ($this->status instanceof AppointmentStatus)
            ? $this->status->label()
            : (AppointmentStatus::tryFrom((string) $this->status)?->label() ?? (string) $this->status);

        $user = $request->user();
        $isPatient = $user && $user->patient;
        $isDoctor = $user && ($user->doctor || $user->hasRole(['doctor', 'super_admin', 'doctor_manager']));
        $isPatientOwner = $user && (
            ($user->patient && $this->patient_id === $user->patient->id) ||
            ($this->relationLoaded('patient') && $this->patient?->user_id === $user->id)
        );
        $isBookedTodayOrFutureAppointment = Carbon::parse($dateStr)->gte(Carbon::today()) && (
            AppointmentStatus::equals($this->status, AppointmentStatus::CONFIRMED) ||
            AppointmentStatus::equals($this->status, AppointmentStatus::RESCHEDULED)
        );

        // Only show opd_type if it's set/non-empty
        $result = [
            'appointment_id' => $this->id,
            'appointment_date' => $appointmentDateTime->format('Y-m-d'),
            'appointment_date_formatted' => $appointmentDateTime->format('j F, Y'),
            'appointment_time' => $appointmentDateTime->format('H:i:s'),
            'appointment_end_time' => $appointmentEndDateTime->format('H:i:s'),
            'appointment_time_formatted' => $appointmentDateTime->format('h:i A'),
            'appointment_end_time_formatted' => $appointmentEndDateTime->format('h:i A'),

            'consultation_type' => $this->consultation_type,
            'consultation_type_label' => $this->consultation_type === 'video'
                ? 'Video consultation'
                : 'Clinic Visit',

            'status' => $this->status,
            'status_label' => $statusLabel,
            'fee_amount' => $this->fee_amount,
            'call_now' => $callNow,
            'notes' => is_array($this->notes) ? implode(', ', $this->notes) : $this->notes,
        ];


        if (! empty($this->availability->opd_type)) {
            $result['opd_type'] = $this->availability->opd_type;
        }

        if ($this->consultation_type === 'video') {
            $result['call_now'] = $callNow;
        }

        // Video consultation block
        if (
            $this->consultation_type === 'video' &&
            $this->relationLoaded('videoConsultation') &&
            $this->videoConsultation
        ) {
            $user = $request->user();
            // Broader check for host authorization (assigned doctor or admin roles)
            $displayName = '';
            if ($user && $isDoctor) {
                // For hosts, use doctor profile name or user name, prefixed with Dr.
                $name = $user->doctor ? ($user->doctor->first_name . ' ' . $user->doctor->last_name) : $user->name;
                $displayName = $name;
            } elseif ($user) {
                // For patients, use patient name or user name
                $displayName = $user->patient ? ($user->patient->first_name . ' ' . $user->patient->last_name) : $user->name;
            }

            // Always give host_url to authorized hosts to bypass 'knocking'
            $baseUrl = $isDoctor
                ? ($this->videoConsultation->host_url ?? $this->videoConsultation->participate_url)
                : $this->videoConsultation->participate_url;

            $joinUrl = $displayName && $baseUrl
                ? $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'displayName=' . urlencode($displayName)
                : $baseUrl;



            $result['video_consultation'] = [
                'id' => $this->videoConsultation->id,
                'room_id' => $this->videoConsultation->room_id,
                'status' => $this->videoConsultation->status,
                'join_url' => $joinUrl,
                'can_join' => $callNow,
                'started_at' => $this->videoConsultation->started_at?->toIso8601String(),
                'ended_at' => $this->videoConsultation->ended_at?->toIso8601String(),
            ];
        }

        // Doctor block
        if (
            ! $isDoctor &&
            $this->relationLoaded('doctor') &&
            $this->doctor &&
            (! $this->doctor->hide_from_mobile_app || $isPatientOwner || $isBookedTodayOrFutureAppointment)
        ) {
            $result['doctor'] = [
                'id' => $this->doctor->id,
                'user_id' => $this->doctor->user_id,
                'name' => $this->doctor->first_name . ' ' . $this->doctor->last_name,
                'avatar' => storage_url($this->doctor->avatar),
                'department' => $this->doctor->departments->first()?->name,
                'slug' => $this->doctor->slug,
                'years_experience' => $this->doctor->years_experience . ' years',
                'average_rating' => round($this->doctor->reviews_avg_rating ?? 0, 1),
                'languages_known' => $this->doctor->languages_known,
            ];
        }

        // Patient block - Only if the current user is NOT a patient
        if (! $isPatient && $this->relationLoaded('patient')) {
            $result['patient'] = [
                'id' => $this->patient->id,
                'user_id' => $this->patient->user_id,
                'name' => $this->patient->first_name . ' ' . $this->patient->last_name,
                'avatar' => storage_url($this->patient->avatar),
                'slug' => $this->patient->slug,
            ];
        }

        return $result;
    }
}