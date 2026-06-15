<?php

namespace App\Http\Resources\Patient;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Common\{MedicalReportResource, AppointmentResource, PrescriptionResource, PreviousAppointmentResource};

class PatientDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $dateOfBirth = $this->date_of_birth ? Carbon::parse($this->date_of_birth) : null;
        $age = $this->age ?? ($dateOfBirth ? $dateOfBirth->age : null);
        $notes = null;

        if ($this->relationLoaded('appointments')) {
            $latestAppointment = $this->appointments
                ->whereNotNull('notes')
                ->first();

            $notes = $latestAppointment?->notes;
        }
        // Build the main array structure
        $data = [
            'id' => $this->id,
            'slug' => $this->slug,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => trim($this->first_name . ' ' . ($this->last_name ?? '')),
            'gender' => $this->gender,
            'gender_label' => $this->gender ? ucfirst($this->gender) : null,
            'date_of_birth' => $this->date_of_birth,
            'age' => $age,
            'age_display' => $age ? $age . ' Years' : null,
            'avatar' => storage_url($this->avatar ?? $this->user?->avatar),
            'notes' => is_array($notes) ? implode(', ', $notes) : $notes,

            // Contact Information
            'contact' => [
                'phone' => $this->mobile_no,
                'phone_formatted' => $this->formatPhone($this->mobile_no),
                'alternate_phone' => $this->alternate_no,
                'email' => $this->email,
            ],

            // Address Information
            'address' => [
                'address' => $this->address,
                'area' => $this->area,
                'city' => $this->city,
                'state' => $this->state,
                'pincode' => $this->pincode,
                'nationality' => $this->nationality,
            ],

            // Additional Info
            'blood_group' => $this->blood_group,
            'marital_status' => $this->marital_status,
            'father_name' => $this->father_name,
            'wife_name' => $this->wife_name,
            'husband_name' => $this->husband_name,
        ];

        // --- UPCOMING_APPOINTMENTS AS FLATTENED SINGLE ITEM OR NULL ---

        if ($this->relationLoaded('upcomingAppointments') && $this->upcomingAppointments && count($this->upcomingAppointments)) {
            $appointment = $this->upcomingAppointments->first();
            $now = Carbon::now();

            // Start datetime
            $start = Carbon::parse($appointment->appointment_date)
                ->setTimeFromTimeString($appointment->appointment_time);

            // End datetime
            if ($appointment->appointment_end_time) {
                $end = Carbon::parse($appointment->appointment_date)
                    ->setTimeFromTimeString($appointment->appointment_end_time);
            } else {
                $end = $start->copy()->addHour();
            }

            // Call window
            $callNow = $appointment->consultation_type === 'video'
                ? $now->between($start->copy()->subHour(), $end)
                : false;

            // Video join link
            $joinUrl = null;
            if (
                $appointment->consultation_type === 'video' &&
                $appointment->relationLoaded('videoConsultation') &&
                $appointment->videoConsultation
            ) {
                $joinUrl = $appointment->videoConsultation->host_url
                    ?? $appointment->videoConsultation->participate_url;
            }

            $data['upcoming_appointments'] = [
                'appointment_id' => $appointment->id,
                'consultation_type' => $appointment->consultation_type,
                'consultation_type_label' => $appointment->consultation_type === 'video'
                    ? 'Video consultation'
                    : 'Clinic Visit',
                'date' => $start->format('D, M Y'), // Tue, Jan 2026
                'time' => $start->format('g:i A'),  // 4:00 PM
                'call_now' => $callNow,
                'video_join_link' => $joinUrl,
            ];
        } else {
            $data['upcoming_appointments'] = null;
        }

        $data['previous_appointments'] = PreviousAppointmentResource::collection($this->whenLoaded('previousAppointments') ?? collect());
        $data['medical_reports'] = MedicalReportResource::collection($this->whenLoaded('medicalReports') ?? collect())->collection;
        $data['current_medications'] = PrescriptionResource::collection($this->whenLoaded('currentMedications') ?? collect());

        return $data;
    }



    /**
     * Format phone number
     */
    protected function formatPhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Format as (XXX) XXX-XXXX if 10 digits
        if (strlen($phone) === 10) {
            return '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6);
        }

        return $phone;
    }
}
