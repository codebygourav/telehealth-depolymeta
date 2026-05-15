<?php

namespace App\Http\Resources\Doctor;

use App\Enums\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorAppoinments extends JsonResource
{
    public function toArray(Request $request): array
    {
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

        // Use Enum label directly
        $statusLabel = ($this->status instanceof AppointmentStatus)
            ? $this->status->label()
            : (AppointmentStatus::tryFrom((string) $this->status)?->label() ?? (string) $this->status);

        $user = $request->user();
        $isPatient = $user && $user->patient;

        // Count total clinic visits and video consultations for the patient
        $totalClinicVisits = 0;
        $totalVideoConsultations = 0;

        // Only count if the patient relationship is loaded and available
        if ($this->relationLoaded('patient') && $this->patient) {
            $appointmentsQuery = $this->patient->appointments();

            $totalClinicVisits = (clone $appointmentsQuery)
                ->where('consultation_type', 'in-person')
                ->count();

            $totalVideoConsultations = (clone $appointmentsQuery)
                ->where('consultation_type', ['video', 'Video'])
                ->count();
        }

        // Only show opd_type if it's set/non-empty
        $result = [
            'appointment_id' => $this->id,
            'appointment_date' => $appointmentDateTime->format('Y-m-d'),
            'appointment_date_formatted' => $appointmentDateTime->format('d/m/y'),
            'appointment_time_formatted' => $appointmentDateTime->format('h:i A'),
            'appointment_end_time_formatted' => $appointmentEndDateTime->format('h:i A'),

            'consultation_type' => $this->consultation_type,
            'consultation_type_label' => $this->consultation_type === 'video'
                ? 'Video consultation'
                : 'Clinic Visit',
            'status' => $this->status,
            'status_label' => $statusLabel,
            'fee_amount' => $this->fee_amount,
            'notes' => is_array($this->notes) ? implode(', ', $this->notes) : $this->notes,
        ];

        if (!empty($this->availability->opd_type)) {
            $result['opd_type'] = $this->availability->opd_type;
        }
        // Patient block - Only if the current user is NOT a patient
        if (!$isPatient && $this->relationLoaded('patient')) {
            $result['patient'] = [
                'id' => $this->patient->id,
                // Only include 4-6 digit patient_id, or null if not valid
                'patient_id' => 'PT-' . substr((string) $this->patient->id, 0, 4),
                'user_id' => $this->patient->user_id,
                'slug' => $this->patient->slug,
                'name' => trim($this->patient->first_name . ' ' . $this->patient->last_name),
                'avatar' => storage_url($this->patient->avatar),
                'email' => $this->patient->user->email,
                'phone' => $this->patient->user->phone,
                'address' => $this->patient->address,
                'pincode' => $this->patient->pincode,
                'area' => $this->patient->area,
                'city' => $this->patient->city,
                'state' => $this->patient->state,
                'landmark' => $this->patient->landmark,
                'total_appointment' => [
                'clinic_visit' => $totalClinicVisits,
                'video_consultation' => $totalVideoConsultations,
            ],
            ];
        }

        return $result;
    }
}
