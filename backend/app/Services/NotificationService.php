<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\Appointment;
use App\Models\DoctorReview;
use App\Models\User;
use App\Models\PatientVaccination;
use App\Notifications\SystemNotification;
use App\Enums\NotificationType;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Notify user when a leave is added for them by an admin.
     */
    public static function notifyLeaveAdded(Leave $leave)
    {
        if ($leave->user && $leave->status === 'approved') {
            $startDate = \Illuminate\Support\Carbon::parse($leave->start_date)->format('M d, Y');
            $endDate = \Illuminate\Support\Carbon::parse($leave->end_date)->format('M d, Y');

            $message = "Your leave from {$startDate} to {$endDate} has been added by the administrator.";

            self::send(
                user: $leave->user,
                type: NotificationType::LEAVE_ADDED->value,
                title: 'New Leave Added',
                message: $message,
                category: 'leave',
                entityType: 'leave',
                entityId: $leave->id,
                meta: [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'type' => $leave->type,
                ]
            );

            return true;
        }

        return false;
    }

    /**
     * Send a notification to a user.
     *
     * @param User $user The user to notify
     * @param string $type The notification type (event_type)
     * @param string $title The notification title
     * @param string $message The notification body message
     * @param string $category The category (appointment/review/report/system)
     * @param string|null $entityType The related entity type (e.g., 'appointment')
     * @param string|int|null $entityId The related entity ID
     * @param array $meta Additional metadata and snapshots
     */
    public static function send($user, string $type, string $title, string $message, string $category, ?string $entityType = null, $entityId = null, array $meta = [])
    {
        if (!$user) {
            Log::warning("NotificationService: Attempted to send notification to null user.");
            return;
        }

        try {
            // Prevent duplicates for critical events within same category/entity
            if ($entityId && $entityType) {
                $exists = \Illuminate\Support\Facades\DB::table('notifications')
                    ->where('notifiable_id', $user->id)
                    ->where('category', $category)
                    ->where('event_type', $type)
                    ->where('entity_id', $entityId)
                    ->where('created_at', '>', now()->subMinutes(5)) // Prevent duplicate within 5 mins
                    ->exists();

                if ($exists) {
                    Log::info("NotificationService: Skipping duplicate notification", [
                        'user_id' => $user->id,
                        'type' => $type,
                        'category' => $category,
                        'entity_id' => $entityId
                    ]);
                    return;
                }
            }

            // Log::info("NotificationService: Sending notification", [
            //     'user_id' => $user->id,
            //     'type' => $type,
            //     'category' => $category,
            //     'entity_id' => $entityId
            // ]);

            $user->notify(new SystemNotification(
                title: $title,
                message: $message,
                type: $type,
                category: $category,
                entityType: $entityType,
                entityId: $entityId,
                meta: $meta
            ));
        } catch (\Exception $e) {
            Log::error("Failed to send notification (type: {$type}, category: {$category}): " . $e->getMessage());
        }
    }

    /**
     * Notify about a confirmed appointment (after payment).
     */
    public static function notifyAppointmentConfirmed(Appointment $appointment)
    {
        $dateStr = $appointment->appointment_date instanceof \Carbon\Carbon
            ? $appointment->appointment_date->format('M d, Y')
            : \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y');

        $timeStr = \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A');

        $snapshot = [
            'doctor_name' => "Dr. {$appointment->doctor->first_name} {$appointment->doctor->last_name}",
            'patient_name' => "{$appointment->patient->first_name} {$appointment->patient->last_name}",
            'appointment_date' => $dateStr,
            'appointment_time' => $timeStr,
            'consultation_type' => $appointment->consultation_type,
        ];

        // Notify Patient
        if ($appointment->patient && $appointment->patient->user) {
            self::send(
                user: $appointment->patient->user,
                type: NotificationType::APPOINTMENT_CONFIRMED->value,
                title: 'Appointment Confirmed',
                message: "Your appointment with Dr. {$appointment->doctor->first_name} on {$dateStr} at {$timeStr} is confirmed.",
                category: 'appointment',
                entityType: 'appointment',
                entityId: $appointment->id,
                meta: $snapshot
            );
        }

        // Notify Doctor
        if ($appointment->doctor && $appointment->doctor->user) {
            self::send(
                user: $appointment->doctor->user,
                type: NotificationType::APPOINTMENT_CONFIRMED->value,
                title: 'New Confirmed Booking',
                message: "New booking from {$appointment->patient->first_name} for {$dateStr} at {$timeStr}.",
                category: 'appointment',
                entityType: 'appointment',
                entityId: $appointment->id,
                meta: $snapshot
            );
        }
    }

    /**
     * Notify about appointment cancellation.
     */
    public static function notifyAppointmentCancelled(Appointment $appointment, string $cancelledBy = 'patient')
    {
        $dateStr = $appointment->appointment_date instanceof \Carbon\Carbon
            ? $appointment->appointment_date->format('M d, Y')
            : \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y');

        $snapshot = [
            'doctor_name' => "Dr. {$appointment->doctor->first_name} {$appointment->doctor->last_name}",
            'patient_name' => "{$appointment->patient->first_name} {$appointment->patient->last_name}",
            'appointment_date' => $dateStr,
        ];

        // Notify Patient
        if ($appointment->patient && $appointment->patient->user) {
            $message = $cancelledBy === 'doctor'
                ? "Your appointment with Dr. {$appointment->doctor->first_name} on {$dateStr} has been cancelled by the doctor."
                : "Your appointment with Dr. {$appointment->doctor->first_name} on {$dateStr} has been successfully cancelled.";

            self::send(
                user: $appointment->patient->user,
                type: NotificationType::APPOINTMENT_CANCELLED->value,
                title: 'Appointment Cancelled',
                message: $message,
                category: 'appointment',
                entityType: 'appointment',
                entityId: $appointment->id,
                meta: $snapshot
            );
        }

        // Notify Doctor
        if ($appointment->doctor && $appointment->doctor->user) {
            $message = $cancelledBy === 'patient'
                ? "Appointment with {$appointment->patient->first_name} for {$dateStr} has been cancelled by the patient."
                : "Appointment with {$appointment->patient->first_name} for {$dateStr} has been cancelled.";

            self::send(
                user: $appointment->doctor->user,
                type: NotificationType::APPOINTMENT_CANCELLED->value,
                title: 'Appointment Cancelled',
                message: $message,
                category: 'appointment',
                entityType: 'appointment',
                entityId: $appointment->id,
                meta: $snapshot
            );
        }
    }

    /**
     * Notify about appointment rescheduling.
     */
    public static function notifyAppointmentRescheduled(Appointment $appointment)
    {
        $dateStr = $appointment->appointment_date instanceof \Carbon\Carbon
            ? $appointment->appointment_date->format('M d, Y')
            : \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y');

        $timeStr = \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A');

        $snapshot = [
            'doctor_name' => "Dr. {$appointment->doctor->first_name} {$appointment->doctor->last_name}",
            'patient_name' => "{$appointment->patient->first_name} {$appointment->patient->last_name}",
            'new_date' => $dateStr,
            'new_time' => $timeStr,
        ];

        // Notify Patient
        if ($appointment->patient && $appointment->patient->user) {
            self::send(
                user: $appointment->patient->user,
                type: NotificationType::APPOINTMENT_RESCHEDULED->value,
                title: 'Appointment Rescheduled',
                message: "Your appointment with Dr. {$appointment->doctor->first_name} has been rescheduled to {$dateStr} at {$timeStr}.",
                category: 'appointment',
                entityType: 'appointment',
                entityId: $appointment->id,
                meta: $snapshot
            );
        }

        // Notify Doctor
        if ($appointment->doctor && $appointment->doctor->user) {
            self::send(
                user: $appointment->doctor->user,
                type: NotificationType::APPOINTMENT_RESCHEDULED->value,
                title: 'Appointment Rescheduled',
                message: "Appointment with {$appointment->patient->first_name} has been rescheduled to {$dateStr} at {$timeStr}.",
                category: 'appointment',
                entityType: 'appointment',
                entityId: $appointment->id,
                meta: $snapshot
            );
        }
    }

    /**
     * Notify about appointment completion.
     */
    public static function notifyAppointmentCompleted(Appointment $appointment)
    {
        // Notify Patient
        if ($appointment->patient && $appointment->patient->user) {
            self::send(
                user: $appointment->patient->user,
                type: NotificationType::APPOINTMENT_COMPLETED->value,
                title: 'Appointment Completed',
                message: "Your appointment with Dr. {$appointment->doctor->first_name} has been marked as completed. We hope you feel better!",
                category: 'appointment',
                entityType: 'appointment',
                entityId: $appointment->id
            );
        }

        // Notify Doctor
        if ($appointment->doctor && $appointment->doctor->user) {
            self::send(
                user: $appointment->doctor->user,
                type: NotificationType::APPOINTMENT_COMPLETED->value,
                title: 'Appointment Completed',
                message: "Appointment with {$appointment->patient->first_name} has been marked as completed.",
                category: 'appointment',
                entityType: 'appointment',
                entityId: $appointment->id
            );
        }
    }

    /**
     * Notify doctor about a new review.
     */
    public static function notifyNewReview(DoctorReview $review)
    {
        if ($review->doctor && $review->doctor->user) {
            $patientName = $review->getPatientNameAttribute();

            self::send(
                user: $review->doctor->user,
                type: NotificationType::REVIEW_ADDED->value,
                title: 'New Review Received',
                message: "You have received a new {$review->rating}-star review from {$patientName}",
                category: 'review',
                entityType: 'review',
                entityId: $review->id,
                meta: [
                    'rating' => $review->rating,
                    'patient_name' => $patientName,
                ]
            );
        }
    }

    /**
     * Notify doctor about new availability slots.
     */
    public static function notifyAvailabilityCreated($doctor, $slots)
    {
        if ($doctor && $doctor->user) {
            $count = is_array($slots) ? count($slots) : (int)$slots;

            if ($count > 1) {
                $message = "{$count} new consultation slots have been added to your schedule by the administrator.";
                if (is_array($slots)) {
                    $details = [];
                    foreach (array_slice($slots, 0, 3) as $s) {
                        $date = !empty($s['date']) ? \Illuminate\Support\Carbon::parse($s['date'])->format('M d') : ucfirst($s['day_of_week'] ?? 'Recurring');
                        $time = !empty($s['start_time']) ? \Illuminate\Support\Carbon::parse($s['start_time'])->format('H:i') : '';
                        $details[] = trim("$date $time");
                    }
                    $message .= " (" . implode(', ', $details) . ($count > 3 ? "..." : "") . ")";
                }
            } else {
                $slot = is_array($slots) ? ($slots[0] ?? null) : null;
                if ($slot) {
                    $date = !empty($slot['date']) ? \Illuminate\Support\Carbon::parse($slot['date'])->format('M d, Y') : ucfirst($slot['day_of_week'] ?? 'Recurring');
                    $time = !empty($slot['start_time']) ? \Illuminate\Support\Carbon::parse($slot['start_time'])->format('H:i') : '';
                    $message = "A new consultation slot for " . trim("$date $time") . " has been added to your schedule by the administrator.";
                } else {
                    $message = "A new consultation slot has been added to your schedule by the administrator.";
                }
            }

            self::send(
                user: $doctor->user,
                type: NotificationType::AVAILABILITY_CREATED->value,
                title: 'New Availability Added',
                message: $message,
                category: 'availability',
                entityType: 'doctor',
                entityId: $doctor->id,
                meta: ['count' => $count, 'slots' => is_array($slots) ? $slots : []]
            );
        }
    }

    /**
     * Notify doctor about availability updates.
     */
    public static function notifyAvailabilityUpdated($doctor, $slots)
    {
        if ($doctor && $doctor->user) {
            $count = is_array($slots) ? count($slots) : (int)$slots;

            if ($count > 1) {
                $message = "{$count} of your consultation slots have been updated by the administrator.";
                if (is_array($slots)) {
                    $details = [];
                    foreach (array_slice($slots, 0, 3) as $s) {
                        $date = !empty($s['date']) ? \Illuminate\Support\Carbon::parse($s['date'])->format('M d') : ucfirst($s['day_of_week'] ?? 'Recurring');
                        $time = !empty($s['start_time']) ? \Illuminate\Support\Carbon::parse($s['start_time'])->format('H:i') : '';
                        $details[] = trim("$date $time");
                    }
                    $message .= " (" . implode(', ', $details) . ($count > 3 ? "..." : "") . ")";
                }
            } else {
                $slot = is_array($slots) ? ($slots[0] ?? null) : null;
                if ($slot) {
                    $date = !empty($slot['date']) ? \Illuminate\Support\Carbon::parse($slot['date'])->format('M d, Y') : ucfirst($slot['day_of_week'] ?? 'Recurring');
                    $time = !empty($slot['start_time']) ? \Illuminate\Support\Carbon::parse($slot['start_time'])->format('H:i') : '';
                    $message = "Your consultation slot for " . trim("$date $time") . " has been updated by the administrator.";
                } else {
                    $message = "One of your consultation slots has been updated by the administrator.";
                }
            }

            self::send(
                user: $doctor->user,
                type: NotificationType::AVAILABILITY_UPDATED->value,
                title: 'Availability Updated',
                message: $message,
                category: 'availability',
                entityType: 'doctor',
                entityId: $doctor->id,
                meta: ['count' => $count, 'slots' => is_array($slots) ? $slots : []]
            );
        }
    }

    /**
     * Notify about appointment failure.
     */
    public static function notifyAppointmentFailed(Appointment $appointment, string $reason = '')
    {
        $dateStr = $appointment->appointment_date instanceof \Carbon\Carbon
            ? $appointment->appointment_date->format('M d, Y')
            : \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y');

        $snapshot = [
            'doctor_name' => "Dr. {$appointment->doctor->first_name} {$appointment->doctor->last_name}",
            'patient_name' => "{$appointment->patient->first_name} {$appointment->patient->last_name}",
            'appointment_date' => $dateStr,
            'reason' => $reason
        ];

        // Notify Patient
        if ($appointment->patient && $appointment->patient->user) {
            self::send(
                user: $appointment->patient->user,
                type: NotificationType::APPOINTMENT_FAILED->value,
                title: 'Appointment Failed',
                message: "Your appointment booking for {$dateStr} has failed." . ($reason ? " Reason: {$reason}" : ""),
                category: 'appointment',
                entityType: 'appointment',
                entityId: $appointment->id,
                meta: $snapshot
            );
        }
    }

    /**
     * Notify doctor about an edited review.
     */
    public static function notifyReviewEdited(DoctorReview $review)
    {
        if ($review->doctor && $review->doctor->user) {
            $patientName = $review->getPatientNameAttribute();

            self::send(
                user: $review->doctor->user,
                type: NotificationType::REVIEW_EDITED->value,
                title: 'Review Updated',
                message: "{$patientName} has updated their review to {$review->rating} stars.",
                category: 'review',
                entityType: 'review',
                entityId: $review->id,
                meta: [
                    'rating' => $review->rating,
                    'patient_name' => $patientName,
                ]
            );
        }
    }

    /**
     * Notify doctor about a deleted review.
     */
    public static function notifyReviewDeleted(DoctorReview $review)
    {
        if ($review->doctor && $review->doctor->user) {
            $patientName = $review->getPatientNameAttribute();

            self::send(
                user: $review->doctor->user,
                type: NotificationType::REVIEW_DELETED->value,
                title: 'Review Deleted',
                message: "{$patientName} has deleted their review.",
                category: 'review',
                entityType: 'review',
                entityId: $review->id,
                meta: [
                    'patient_name' => $patientName,
                ]
            );
        }
    }

    /**
     * Send a reminder before an appointment.
     */
    public static function notifyAppointmentReminder(Appointment $appointment, int $minutes)
    {
        $timeStr = \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A');

        $type = match ($minutes) {
            15 => NotificationType::APPOINTMENT_REMINDER_15->value,
            30 => NotificationType::APPOINTMENT_REMINDER_30->value,
            45 => NotificationType::APPOINTMENT_REMINDER_45->value,
            60 => NotificationType::APPOINTMENT_REMINDER_1_HOUR->value,
            1440 => NotificationType::APPOINTMENT_REMINDER_1_DAY->value,
            default => NotificationType::APPOINTMENT_REMINDER_15->value, // Fallback
        };

        $snapshot = [
            'doctor_name' => "Dr. {$appointment->doctor->first_name} {$appointment->doctor->last_name}",
            'patient_name' => "{$appointment->patient->first_name} {$appointment->patient->last_name}",
            'appointment_time' => $timeStr,
            'minutes' => $minutes,
        ];

        $timePhrase = match ($minutes) {
            1440 => "tomorrow at",
            60 => "in 1 hour at",
            default => "in {$minutes} minutes at",
        };

        // Notify Patient
        if ($appointment->patient && $appointment->patient->user) {
            self::send(
                user: $appointment->patient->user,
                type: $type,
                title: 'Appointment Reminder',
                message: "Reminder: You have an appointment with Dr. {$appointment->doctor->first_name} {$timePhrase} {$timeStr}.",
                category: 'appointment',
                entityType: 'appointment',
                entityId: $appointment->id,
                meta: $snapshot
            );
        }

        // Notify Doctor
        if ($appointment->doctor && $appointment->doctor->user) {
            self::send(
                user: $appointment->doctor->user,
                type: $type,
                title: 'Appointment Reminder',
                message: "Reminder: You have an appointment with {$appointment->patient->first_name} {$timePhrase} {$timeStr}.",
                category: 'appointment',
                entityType: 'appointment',
                entityId: $appointment->id,
                meta: $snapshot
            );
        }
    }

    /**
     * Notify doctor when patient knocks in on a video call.
     */
    public static function notifyPatientKnocks(Appointment $appointment)
    {
        if ($appointment->doctor && $appointment->doctor->user) {
            $patientName = "{$appointment->patient->first_name} {$appointment->patient->last_name}";
            self::send(
                user: $appointment->doctor->user,
                type: NotificationType::PATIENT_KNOCKS_VIDEO_CALL->value,
                title: 'Patient Knocking In',
                message: "{$patientName} is knocking and waiting for the video call.",
                category: 'appointment',
                entityType: 'appointment',
                entityId: $appointment->id,
                meta: [
                    'patient_name' => $patientName,
                ]
            );
        }
    }

    /**
     * Notify about user joining video call.
     */
    public static function notifyVideoCallJoined(Appointment $appointment, string $roleName, string $displayName = '')
    {
        // When Doctor joins
        if ($roleName === 'owner' || $roleName === 'host') {
            // Notify Patient that doctor has joined
            if ($appointment->patient && $appointment->patient->user) {
                self::send(
                    user: $appointment->patient->user,
                    type: NotificationType::VIDEO_CALL_JOINED->value,
                    title: 'Doctor Joined Video Call',
                    message: "Dr. {$displayName} has joined the video call.",
                    category: 'appointment',
                    entityType: 'appointment',
                    entityId: $appointment->id,
                    meta: ['display_name' => $displayName, 'role' => $roleName]
                );
            }
        } else {
            // When Patient joins
            // Notify Doctor that patient has joined
            if ($appointment->doctor && $appointment->doctor->user) {
                $displayName = $displayName ?: "{$appointment->patient->first_name} {$appointment->patient->last_name}";
                self::send(
                    user: $appointment->doctor->user,
                    type: NotificationType::VIDEO_CALL_JOINED->value,
                    title: 'Patient Joined Video Call',
                    message: "{$displayName} has joined the video call room.",
                    category: 'appointment',
                    entityType: 'appointment',
                    entityId: $appointment->id,
                    meta: ['display_name' => $displayName, 'role' => $roleName]
                );
            }
        }
    }

    /**
     * Notify about user leaving video call.
     */
    public static function notifyVideoCallLeft(Appointment $appointment, string $roleName, string $displayName = '')
    {
        // When Doctor left
        if ($roleName === 'owner' || $roleName === 'host') {
            // Notify Patient that doctor left
            if ($appointment->patient && $appointment->patient->user) {
                self::send(
                    user: $appointment->patient->user,
                    type: NotificationType::VIDEO_CALL_LEFT->value,
                    title: 'Doctor Left Video Call',
                    message: "Dr. {$displayName} has left the video call.",
                    category: 'appointment',
                    entityType: 'appointment',
                    entityId: $appointment->id,
                    meta: ['display_name' => $displayName, 'role' => $roleName]
                );
            }
        } else {
            // When Patient left
            // Notify Doctor that patient left
            if ($appointment->doctor && $appointment->doctor->user) {
                $displayName = $displayName ?: "{$appointment->patient->first_name} {$appointment->patient->last_name}";
                self::send(
                    user: $appointment->doctor->user,
                    type: NotificationType::VIDEO_CALL_LEFT->value,
                    title: 'Patient Left Video Call',
                    message: "{$displayName} has left the video call.",
                    category: 'appointment',
                    entityType: 'appointment',
                    entityId: $appointment->id,
                    meta: ['display_name' => $displayName, 'role' => $roleName]
                );
            }
        }
    }

    /**
     * Notify doctor when patient cancels knocking.
     */
    public static function notifyVideoCallKnockCancelled(Appointment $appointment)
    {
        if ($appointment->doctor && $appointment->doctor->user) {
            $patientName = "{$appointment->patient->first_name} {$appointment->patient->last_name}";
            self::send(
                user: $appointment->doctor->user,
                type: NotificationType::VIDEO_CALL_KNOCK_CANCELLED->value, // Assume this exists in enum
                title: 'Knock Cancelled',
                message: "{$patientName} stopped knocking and left the waiting room.",
                category: 'appointment',
                entityType: 'appointment',
                entityId: $appointment->id,
                meta: [
                    'patient_name' => $patientName,
                ]
            );
        }
    }

    /**
     * Notify patient when a doctor adds a new prescription.
     */
    public static function notifyPrescriptionAdded(Appointment $appointment)
    {
        if ($appointment->patient && $appointment->patient->user) {
            $doctorName = "Dr. {$appointment->doctor->first_name} {$appointment->doctor->last_name}";
            self::send(
                user: $appointment->patient->user,
                type: NotificationType::PRESCRIPTION_ADDED->value,
                title: 'New Prescription Added',
                message: "{$doctorName} has added a new prescription for your consultation.",
                category: 'prescription',
                entityType: 'prescription',
                entityId: $appointment->id,
                meta: [
                    'doctor_name' => $doctorName,
                ]
            );
        }
    }

    /**
     * Notify patient when a doctor updates instructions.
     */
    public static function notifyDoctorInstructionsAdded(Appointment $appointment)
    {
        if ($appointment->patient && $appointment->patient->user) {
            $doctorName = "Dr. {$appointment->doctor->first_name} {$appointment->doctor->last_name}";
            self::send(
                user: $appointment->patient->user,
                type: NotificationType::DOCTOR_INSTRUCTIONS_ADDED->value,
                title: 'Doctor Instructions Updated',
                message: "{$doctorName} has updated instructions for your consultation.",
                category: 'appointment',
                entityType: 'appointment',
                entityId: $appointment->id,
                meta: [
                    'doctor_name' => $doctorName,
                ]
            );
        }
    }

    /**
     * Notify patient when a vaccination dose is due or approaching due.
     */
    public static function notifyVaccinationDue(PatientVaccination $vaccination, string $daysLabel)
    {
        if ($vaccination->patient && $vaccination->patient->user) {
            $vaccineName = $vaccination->vaccination?->name ?: 'Vaccine';
            $dueDate = $vaccination->due_date ? $vaccination->due_date->format('M d, Y') : '—';
            $patientName = trim(($vaccination->patient?->first_name ?? '') . ' ' . ($vaccination->patient?->last_name ?? '')) ?: 'your account';

            $message = "The vaccination dose for {$vaccineName} ({$patientName}) is {$daysLabel}. Due date: {$dueDate}.";

            self::send(
                user: $vaccination->patient->user,
                type: NotificationType::VACCINATION_DUE->value,
                title: 'Vaccination Dose Due Alert',
                message: $message,
                category: 'system',
                entityType: 'patient_vaccination',
                entityId: $vaccination->id,
                meta: [
                    'vaccine_name' => $vaccineName,
                    'due_date' => $dueDate,
                    'days_label' => $daysLabel,
                    'dose_no' => $vaccination->dose_no,
                    'patient_name' => $patientName,
                ]
            );
            return true;
        }
        return false;
    }

    /**
     * Notify patient when a vaccination dose is overdue.
     */
    public static function notifyVaccinationOverdue(PatientVaccination $vaccination)
    {
        if ($vaccination->patient && $vaccination->patient->user) {
            $vaccineName = $vaccination->vaccination?->name ?: 'Vaccine';
            $dueDate = $vaccination->due_date ? $vaccination->due_date->format('M d, Y') : '—';
            $patientName = trim(($vaccination->patient?->first_name ?? '') . ' ' . ($vaccination->patient?->last_name ?? '')) ?: 'your account';

            $message = "WARNING: The vaccination dose for {$vaccineName} ({$patientName}) was due on {$dueDate} and is now OVERDUE.";

            self::send(
                user: $vaccination->patient->user,
                type: NotificationType::VACCINATION_OVERDUE->value,
                title: 'Vaccination OVERDUE Warning',
                message: $message,
                category: 'system',
                entityType: 'patient_vaccination',
                entityId: $vaccination->id,
                meta: [
                    'vaccine_name' => $vaccineName,
                    'due_date' => $dueDate,
                    'dose_no' => $vaccination->dose_no,
                    'patient_name' => $patientName,
                ]
            );
            return true;
        }
        return false;
    }

    /**
     * Notify patient when a vaccination dose is marked completed.
     */
    public static function notifyVaccinationCompleted(PatientVaccination $vaccination)
    {
        if ($vaccination->patient && $vaccination->patient->user) {
            $vaccineName = $vaccination->vaccination?->name ?: 'Vaccine';
            $completedDate = $vaccination->completed_date ? $vaccination->completed_date->format('M d, Y') : '—';
            $patientName = trim(($vaccination->patient?->first_name ?? '') . ' ' . ($vaccination->patient?->last_name ?? '')) ?: 'your account';

            $message = "Great news! The vaccination dose for {$vaccineName} ({$patientName}) has been successfully administered and marked completed on {$completedDate}.";

            self::send(
                user: $vaccination->patient->user,
                type: NotificationType::VACCINATION_COMPLETED->value,
                title: 'Vaccination Dose Completed',
                message: $message,
                category: 'system',
                entityType: 'patient_vaccination',
                entityId: $vaccination->id,
                meta: [
                    'vaccine_name' => $vaccineName,
                    'completed_date' => $completedDate,
                    'dose_no' => $vaccination->dose_no,
                    'patient_name' => $patientName,
                ]
            );
            return true;
        }
        return false;
    }

    /**
     * Notify patient when a vaccination dose has been missed (past grace period).
     */
    public static function notifyVaccinationMissed(PatientVaccination $vaccination)
    {
        if ($vaccination->patient && $vaccination->patient->user) {
            $vaccineName = $vaccination->vaccination?->name ?: 'Vaccine';
            $dueDate = $vaccination->due_date ? $vaccination->due_date->format('M d, Y') : '—';
            $patientName = trim(($vaccination->patient?->first_name ?? '') . ' ' . ($vaccination->patient?->last_name ?? '')) ?: 'your account';

            $message = "Alert: The vaccination dose for {$vaccineName} ({$patientName}) due on {$dueDate} has been marked as MISSED. Please contact your doctor to reschedule.";

            self::send(
                user: $vaccination->patient->user,
                type: NotificationType::VACCINATION_MISSED->value,
                title: 'Vaccination Dose Missed',
                message: $message,
                category: 'system',
                entityType: 'patient_vaccination',
                entityId: $vaccination->id,
                meta: [
                    'vaccine_name' => $vaccineName,
                    'due_date' => $dueDate,
                    'dose_no' => $vaccination->dose_no,
                    'patient_name' => $patientName,
                ]
            );
            return true;
        }
        return false;
    }

    /**
     * Notify patient and doctor when patient checks in.
     */
    public static function notifyPatientCheckedIn(Appointment $appointment)
    {
        $queueNo = $appointment->queue_number ?: '—';
        $patientName = $appointment->patient ? "{$appointment->patient->first_name} {$appointment->patient->last_name}" : 'Patient';
        $doctorName = $appointment->doctor ? "Dr. {$appointment->doctor->first_name} {$appointment->doctor->last_name}" : 'Doctor';

        // Notify Patient
        if ($appointment->patient && $appointment->patient->user) {
            self::send(
                user: $appointment->patient->user,
                type: NotificationType::PATIENT_CHECKED_IN->value,
                title: 'Checked-In Successfully',
                message: "You have been checked in for your appointment with {$doctorName}. Your queue number is {$queueNo}.",
                category: 'appointment',
                entityType: 'appointment',
                entityId: $appointment->id,
                meta: [
                    'queue_number' => $queueNo,
                    'doctor_name' => $doctorName,
                ]
            );
        }

        // Notify Doctor
        if ($appointment->doctor && $appointment->doctor->user) {
            self::send(
                user: $appointment->doctor->user,
                type: NotificationType::PATIENT_CHECKED_IN->value,
                title: 'Patient Checked In',
                message: "Your patient {$patientName} has checked in (Queue No: {$queueNo}) and is waiting.",
                category: 'appointment',
                entityType: 'appointment',
                entityId: $appointment->id,
                meta: [
                    'queue_number' => $queueNo,
                    'patient_name' => $patientName,
                ]
            );
        }
    }

    /**
     * Notify patient when consultation starts (transitions to running status).
     */
    public static function notifyConsultationStarted(Appointment $appointment)
    {
        $queueNo = $appointment->queue_number ?: '—';
        $doctorName = $appointment->doctor ? "Dr. {$appointment->doctor->first_name} {$appointment->doctor->last_name}" : 'Doctor';
        $room = $appointment->doctor?->address_line2 ?: 'doctor\'s room';

        // Notify Patient
        if ($appointment->patient && $appointment->patient->user) {
            self::send(
                user: $appointment->patient->user,
                type: NotificationType::CONSULTATION_STARTED->value,
                title: 'Consultation Started',
                message: "Your consultation with {$doctorName} has started. Please proceed to {$room} or join the video call.",
                category: 'appointment',
                entityType: 'appointment',
                entityId: $appointment->id,
                meta: [
                    'queue_number' => $queueNo,
                    'doctor_name' => $doctorName,
                ]
            );
        }
    }

    /**
     * Notify patient when a vaccination is assigned to them.
     */
    public static function notifyVaccinationAssigned(PatientVaccination $vaccination)
    {
        if ($vaccination->patient && $vaccination->patient->user) {
            $vaccineName = $vaccination->vaccination?->name ?: 'Vaccine';
            $dueDate = $vaccination->due_date ? $vaccination->due_date->format('M d, Y') : '—';

            self::send(
                user: $vaccination->patient->user,
                type: 'vaccination_assigned',
                title: 'New Vaccination Assigned',
                message: "A new vaccination dose for {$vaccineName} has been assigned to you. Due date: {$dueDate}.",
                category: 'vaccination',
                entityType: 'patient_vaccination',
                entityId: $vaccination->id,
                meta: [
                    'vaccine_name' => $vaccineName,
                    'due_date' => $dueDate,
                    'dose_no' => $vaccination->dose_no,
                ]
            );
            return true;
        }
        return false;
    }

    /**
     * Notify patient to take their medicine.
     */
    public static function notifyMedicineReminder(Prescription $prescription, string $time)
    {
        if ($prescription->patient && $prescription->patient->user) {
            $medName = $prescription->medicine_name ?: 'Medicine';
            $dosage = $prescription->dosage ?: '';
            $timing = $prescription->meal_timing ? " - {$prescription->meal_timing}" : '';

            self::send(
                user: $prescription->patient->user,
                type: 'medicine_reminder',
                title: 'Medicine Intake Reminder',
                message: "Reminder: It is time to take your medicine: {$medName} {$dosage}{$timing} scheduled for {$time}.",
                category: 'prescription',
                entityType: 'prescription',
                entityId: $prescription->id,
                meta: [
                    'medicine_name' => $medName,
                    'dosage' => $dosage,
                    'meal_timing' => $prescription->meal_timing,
                    'time' => $time,
                ]
            );
            return true;
        }
        return false;
    }

    /**
     * Notify patient when a doctor assigns a diet plan.
     */
    public static function notifyDietPlanAssigned(\App\Models\PatientDietPlan $plan)
    {
        // 1. Notify Patient
        if ($plan->patient && $plan->patient->user) {
            $doctorName = $plan->doctor ? "Dr. {$plan->doctor->first_name} {$plan->doctor->last_name}" : "your Doctor";
            $message = "A new diet plan '{$plan->template_name}' has been assigned to you by {$doctorName} starting on " . \Illuminate\Support\Carbon::parse($plan->start_date)->format('M d, Y') . ".";

            self::send(
                user: $plan->patient->user,
                type: NotificationType::DIET_PLAN_ASSIGNED->value,
                title: 'New Diet Plan Assigned',
                message: $message,
                category: 'system',
                entityType: 'patient_diet_plan',
                entityId: $plan->id,
                meta: [
                    'plan_name' => $plan->template_name,
                    'doctor_name' => $doctorName,
                    'start_date' => $plan->start_date instanceof \Illuminate\Support\Carbon ? $plan->start_date->toDateString() : (is_string($plan->start_date) ? $plan->start_date : ''),
                    'duration_days' => $plan->duration_days,
                ]
            );
        }

        // 2. Notify Doctor (creator)
        if ($plan->doctor && $plan->doctor->user) {
            $patientName = trim(($plan->patient?->first_name ?? '') . ' ' . ($plan->patient?->last_name ?? '')) ?: 'Patient';
            $message = "Diet plan '{$plan->template_name}' has been successfully assigned to {$patientName}.";

            self::send(
                user: $plan->doctor->user,
                type: NotificationType::DIET_PLAN_ASSIGNED->value,
                title: 'Diet Plan Assigned Successfully',
                message: $message,
                category: 'system',
                entityType: 'patient_diet_plan',
                entityId: $plan->id,
                meta: [
                    'plan_name' => $plan->template_name,
                    'patient_name' => $patientName,
                    'start_date' => $plan->start_date instanceof \Illuminate\Support\Carbon ? $plan->start_date->toDateString() : (is_string($plan->start_date) ? $plan->start_date : ''),
                ]
            );
        }
    }
}
