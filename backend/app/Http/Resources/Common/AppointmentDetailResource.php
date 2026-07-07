<?php

namespace App\Http\Resources\Common;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\AppointmentStatus;
use App\Http\Resources\Common\PreviousAppointmentResource;
use App\Models\DoctorReview;

class AppointmentDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $appointmentDate = $this->appointment_date ? Carbon::parse($this->appointment_date) : null;
        $appointmentTime = $this->appointment_time ? Carbon::parse($this->appointment_time) : null;
        $now = Carbon::now();

        // Defensive: If either date or time is missing, fallback to safe values
        $appointmentDateTime = ($appointmentDate && $appointmentTime)
            ? $appointmentDate->copy()->setTimeFromTimeString($appointmentTime->format('H:i:s'))
            : null;

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

        // Fix: Ensure $callNow is only true if all required pieces are valid
        $callNow = false;
        $canStartVideoCall = AppointmentStatus::equals($this->status, AppointmentStatus::CONFIRMED) ||
            AppointmentStatus::equals($this->status, AppointmentStatus::RESCHEDULED);

        if (
            $appointmentDateTime && $appointmentEndDateTime &&
            ($this->consultation_type === 'video' || $this->consultation_type === 'Video') &&
            $now->isSameDay($appointmentDateTime) &&
            $canStartVideoCall
        ) {
            // Window: 1h before start → end time, inclusive of bounds
            $callWindowStart = $appointmentDateTime->copy()->subHour();
            $callWindowEnd = $appointmentEndDateTime;
            $callNow = $now->greaterThanOrEqualTo($callWindowStart) && $now->lessThanOrEqualTo($callWindowEnd);
        }

        // Defensive: If any part is missing, we can't cancel/reschedule
        $canCancelOrReschedule = ($appointmentDateTime)
            ? $now->copy()->addHour()->lte($appointmentDateTime)
            : false;

        // Check if the patient can add a review for this doctor
        $patientId = $this->patient?->id;
        $doctorId = $this->doctor_id;

        $existingReview = null;
        if ($patientId && $doctorId) {
            $existingReview = DoctorReview::where('doctor_id', $doctorId)
                ->where('patient_id', $patientId)
                ->first();
        }

        $allowedToAddReview = ($patientId && $doctorId && !$existingReview);

        // Instead of $this->doctorReviews(), fetch review for this appointment if loaded
        $appointmentReview = $this->relationLoaded('doctorReviews')
            ? $this->doctorReviews->first()
            : null;

        // === Corrected logic for average & total reviews below ===
        $doctorAverageRating = null;
        $doctorTotalReviews = 0;
        if ($this->relationLoaded('doctor') && $this->doctor) {
            // Defensive: only if relation loaded!
            // Don't call $this->doctor->doctorReviews(), but read doctorReviews through relationship if loaded
            if ($this->doctor->relationLoaded('doctorReviews')) {
                $doctorTotalReviews = $this->doctor->doctorReviews->count();
                $doctorAverageRating = $this->doctor->doctorReviews->avg('rating');
            } else {
                // fallback: query the reviews directly (if relation not loaded)
                $doctorTotalReviews = DoctorReview::where('doctor_id', $this->doctor->id)->count();
                $doctorAverageRating = DoctorReview::where('doctor_id', $this->doctor->id)->avg('rating');
            }
            if ($doctorAverageRating !== null) {
                $doctorAverageRating = round($doctorAverageRating, 1);
            }
        }

        // Video Consultation Join URL logic
        $user = $request->user();
        $isDoctor = $user && ($user->doctor || $user->hasRole(['doctor', 'super_admin', 'doctor_manager']));
        $isPatientOwner = $user && (
            ($user->patient && $this->patient_id === $user->patient->id) ||
            ($this->relationLoaded('patient') && $this->patient?->user_id === $user->id)
        );
        $isBookedTodayOrFutureAppointment = Carbon::parse($dateStr)->gte(Carbon::today()) && (
            AppointmentStatus::equals($this->status, AppointmentStatus::CONFIRMED) ||
            AppointmentStatus::equals($this->status, AppointmentStatus::RESCHEDULED)
        );
        $canShowDoctor = $this->relationLoaded('doctor') &&
            $this->doctor &&
            (! $this->doctor->hide_from_mobile_app || $isPatientOwner || $isDoctor || $isBookedTodayOrFutureAppointment);
        $joinUrl = null;

        if (
            ($this->consultation_type === 'video' || $this->consultation_type === 'Video') &&
            $this->relationLoaded('videoConsultation') &&
            $this->videoConsultation
        ) {
            $displayName = '';
            if ($user && $isDoctor) {
                $name = $user->doctor ? ($user->doctor->first_name . ' ' . $user->doctor->last_name) : $user->name;
                $displayName = $name;
            } elseif ($user) {
                $displayName = $user->patient ? ($user->patient->first_name . ' ' . $user->patient->last_name) : $user->name;
            }

            $baseUrl = $isDoctor
                ? ($this->videoConsultation->host_url ?? $this->videoConsultation->participate_url)
                : $this->videoConsultation->participate_url;

            $joinUrl = $displayName && $baseUrl
                ? $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'displayName=' . urlencode($displayName)
                : $baseUrl;
        }

        return [
            'appointment_id' => $this->id,
            // Schedule
            'schedule' => [
                'date' => $appointmentDate ? $appointmentDate->format('Y-m-d') : null,
                'date_formatted' => $appointmentDate ? $appointmentDate->format('d M, y | l') : null,
                'date_format' => $appointmentDate ? $appointmentDate->format('j F, Y') : null,
                'day_format' => $appointmentDate ? $appointmentDate->format('l') : null,
                'time' => $appointmentTime ? $appointmentTime->format('H:i:s') : null,
                'time_formatted' => $appointmentTime ? $appointmentTime->format('h:i A') : null,
                'booking_type' => ($this->consultation_type === 'video') ? 'Online' : 'In-Person',
                'consultation_type' => $this->consultation_type,
                'consultation_type_label' => $this->consultation_type === 'video'
                    ? 'Video Consultation'
                    : 'Clinic Visit',
                'opd_type' => ($this->consultation_type == 'in-person' && $this->availability)
                    ? $this->availability->opd_type
                    : null,
            ],

            // Status
            'status' => $this->status instanceof AppointmentStatus
                ? $this->status->value
                : $this->status,
            'status_label' => ($appointmentDate && $appointmentTime)
                ? $this->getStatusLabel($appointmentDate, $appointmentTime, $now)
                : 'Scheduled',

            'can_start_consultation' => ($appointmentDate && $appointmentTime)
                ? $this->canStartConsultation($appointmentDate, $appointmentTime, $now)
                : false,
            'can_cancel' => $canCancelOrReschedule,
            'can_reschedule' => $canCancelOrReschedule,
            'call_now' => $callNow,
            'join_url' => ($callNow && !empty($joinUrl)) ? $joinUrl : '',

            // Patient
            'patient' => $this->whenLoaded('patient', function () {
                return [
                    'id' => $this->patient->id,
                    'name' => trim(($this->patient->first_name ?? '') . ' ' . ($this->patient->last_name ?? '')),
                    'first_name' => $this->patient->first_name,
                    'last_name' => $this->patient->last_name,
                    'age' => $this->patient->age,
                    'age_formatted' => $this->patient->age ? $this->patient->age . ' Years' : null,
                    'gender' => $this->patient->gender,
                    'gender_formatted' => ucfirst($this->patient->gender ?? ''),
                    'avatar' => isset($this->patient->user) ? storage_url($this->patient->avatar ?? null) : null,
                    'phone' => $this->patient->mobile_no,
                    'email' => $this->patient->email,
                    'blood_group' => $this->patient->blood_group,
                    'problem' => is_array($this->notes) ? implode(', ', $this->notes) : $this->notes,
                ];
            }),

            'doctor' => $canShowDoctor ? $this->whenLoaded('doctor', function () use ($appointmentReview, $doctorAverageRating, $doctorTotalReviews) {
                return [
                    'id' => $this->doctor->id,
                    'user_id' => $this->doctor->user?->id,
                    'name' => trim(($this->doctor->first_name ?? '') . ' ' . ($this->doctor->last_name ?? '')),
                    'first_name' => $this->doctor->first_name,
                    'last_name' => $this->doctor->last_name,
                    'avatar' => storage_url($this->doctor->avatar ?? null),
                    'years_experience' => isset($this->doctor->years_experience)
                        ? $this->doctor->years_experience . ' ' .
                        ($this->doctor->years_experience == 1 ? 'Year' : 'Years')
                        : null,
                    'department' => $this->doctor->departments->first()?->name,
                    'average_rating' => $doctorAverageRating,
                    'total_reviews' => $doctorTotalReviews,
                    'review' => $appointmentReview ? [
                        'id' => $appointmentReview->id,
                        'title' => $appointmentReview->title,
                        'content' => $appointmentReview->content,
                        'rating' => $appointmentReview->rating,
                        'created_at' => $appointmentReview->created_at?->format('d M Y'),
                    ] : [],
                ];
            }) : null,
            // Force to false if already reviewed for any appointment
            'can_add_review' => $allowedToAddReview,

            // Payment
            'razorpay_key_id' => env('RAZORPAY_KEY_ID'),
            'razorpay_order_id' => $this->payment?->razorpay_order_id,
            'payment' => $this->getPaymentDetails(),

            // Medical Reports
            'medical_reports' => $this->whenLoaded('medicalReports', function () use ($canShowDoctor) {
                return $this->medicalReports->map(function ($report) use ($canShowDoctor) {
                    return [
                        'id' => $report->id,
                        'title' => $report->name,
                        'type' => $report->type,
                        'type_label' => $report->type_label
                            ? ucfirst(str_replace('_', ' ', $report->type_label))
                            : null,
                        'report_uploaded_by' => $report->uploader_type,
                        'report_date' => $report->report_date?->format('Y-m-d'),
                        'report_date_formatted' => $report->report_date?->format('D, M d'),
                        'doctor_name' => $canShowDoctor && isset($report->doctor)
                            ? (trim(($report->doctor->first_name ?? '') . ' ' . ($report->doctor->last_name ?? '')))
                            : null,
                        'file_url' => $report->file_url,
                        'status' => $report->status,
                    ];
                });
            }),

            // Prescriptions
            'prescriptions' => $this->whenLoaded('prescriptions', function () use ($canShowDoctor) {
                $prescription = $this->prescriptions->first();
                if (!$prescription) {
                    return null;
                }

                $doctor = $this->doctor ?? null;

                return (object)[
                    'doctor_name' => $doctor && (! $doctor->hide_from_mobile_app || $canShowDoctor)
                        ? $doctor->first_name . ' ' . $doctor->last_name
                        : null,
                    'notes' => is_array($this->notes) ? implode(', ', $this->notes) : $this->notes,
                    'date' => $this->appointment_date
                        ? \Carbon\Carbon::parse($this->appointment_date)->format('D, d M Y')
                        : null,
                ];
            }),

            // Previous Appointments (doctor only)
            'previous_appointments' => $this->when(
                $this->relationLoaded('patient') && request()->user()?->doctor,
                fn() => PreviousAppointmentResource::collection($this->previousAppointments)
            ),

            'notes' => is_array($this->notes) ? implode(', ', $this->notes) : $this->notes,
        ];
    }

    protected function getPaymentDetails(): array
    {
        $payment = $this->relationLoaded('payment') ? $this->payment : null;
        $consultationFee = $this->fee_amount ?? 0;
        $adminFee = $payment?->fee ?? 0;

        return [
            'id' => $payment?->id,
            'order_id' => $payment?->razorpay_order_id,
            'payment_id' => $payment?->razorpay_payment_id,
            'key_id' => $payment?->key_id,
            'consultation_fee' => $consultationFee,
            'consultation_fee_formatted' => currency_symbol() . number_format($consultationFee, 2),
            'admin_fee' => $adminFee,
            'admin_fee_formatted' => currency_symbol() . number_format($adminFee, 2),
            'discount' => 0,
            'discount_formatted' => '-',
            'total' => $consultationFee + $adminFee,
            'total_formatted' => currency_symbol() . number_format($consultationFee + $adminFee, 2),
            'status' => isset($payment->status) ? (is_object($payment->status) ? $payment->status->value : $payment->status) : null,
            'status_label' => isset($payment->status) && is_object($payment->status) && method_exists($payment->status, 'label')
                ? $payment->status->label()
                : null,
            'payment_method' => $payment?->payment_method,
            'transaction_id' => $payment?->transaction_id,
        ];
    }

    protected function getStatusLabel(Carbon $appointmentDate, Carbon $appointmentTime, Carbon $now): string
    {
        if (AppointmentStatus::equals($this->status, AppointmentStatus::CANCELLED)) {
            return AppointmentStatus::CANCELLED->label();
        }
        if (AppointmentStatus::equals($this->status, AppointmentStatus::CONFIRMED)) {
            return AppointmentStatus::CONFIRMED->label();
        }
        if (AppointmentStatus::equals($this->status, AppointmentStatus::RESCHEDULED)) {
            return AppointmentStatus::RESCHEDULED->label();
        }
        if (AppointmentStatus::equals($this->status, AppointmentStatus::FAILED)) {
            return AppointmentStatus::FAILED->label();
        }

        if (AppointmentStatus::equals($this->status, AppointmentStatus::COMPLETED)) {
            return AppointmentStatus::COMPLETED->label();
        }

        $appointmentDateTime = $appointmentDate
            ->copy()
            ->setTimeFromTimeString($appointmentTime->format('H:i:s'));

        if ($appointmentDateTime->lt($now) && AppointmentStatus::equals($this->status, AppointmentStatus::CONFIRMED)) {
            return AppointmentStatus::COMPLETED->label();
        }

        if ($appointmentDate->isToday()) {
            $joinWindow = $appointmentDateTime->copy()->subMinutes(30);
            if ($now->between($joinWindow, $appointmentDateTime->copy()->addHour())) {
                return 'Ready to join';
            }

            return 'Today';
        }

        $status = $this->status instanceof AppointmentStatus
            ? $this->status
            : AppointmentStatus::tryFrom($this->status);

        return $status ? $status->label() : 'Scheduled';
    }

    protected function canStartConsultation(Carbon $appointmentDate, Carbon $appointmentTime, Carbon $now): bool
    {
        if (
            AppointmentStatus::equals($this->status, AppointmentStatus::CANCELLED) ||
            AppointmentStatus::equals($this->status, AppointmentStatus::COMPLETED)
        ) {
            return false;
        }

        $appointmentDateTime = $appointmentDate
            ->copy()
            ->setTimeFromTimeString($appointmentTime->format('H:i:s'));

        return $now->between(
            $appointmentDateTime->copy()->subMinutes(15),
            $appointmentDateTime->copy()->addHour()
        );
    }
}