<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\Appointment;
use App\Models\DoctorReview;
use App\Models\User;
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
            'doctor_name' => "{$appointment->doctor->first_name} {$appointment->doctor->last_name}",
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
                message: "Your appointment with {$appointment->doctor->first_name} on {$dateStr} at {$timeStr} is confirmed.",
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
            'doctor_name' => "{$appointment->doctor->first_name} {$appointment->doctor->last_name}",
            'patient_name' => "{$appointment->patient->first_name} {$appointment->patient->last_name}",
            'appointment_date' => $dateStr,
        ];

        // Notify Patient
        if ($appointment->patient && $appointment->patient->user) {
            $message = $cancelledBy === 'doctor'
                ? "Your appointment with {$appointment->doctor->first_name} on {$dateStr} has been cancelled by the doctor."
                : "Your appointment with {$appointment->doctor->first_name} on {$dateStr} has been successfully cancelled.";

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
            'doctor_name' => "{$appointment->doctor->first_name} {$appointment->doctor->last_name}",
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
                message: "Your appointment with {$appointment->doctor->first_name} has been rescheduled to {$dateStr} at {$timeStr}.",
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
                message: "Your appointment with {$appointment->doctor->first_name} has been marked as completed. We hope you feel better!",
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
            'doctor_name' => "{$appointment->doctor->first_name} {$appointment->doctor->last_name}",
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
            'doctor_name' => "{$appointment->doctor->first_name} {$appointment->doctor->last_name}",
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
                message: "Reminder: You have an appointment with {$appointment->doctor->first_name} {$timePhrase} {$timeStr}.",
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
                    message: "{$displayName} has joined the video call.",
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
                    message: "{$displayName} has left the video call.",
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
            $doctorName = "{$appointment->doctor->first_name} {$appointment->doctor->last_name}";
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
            $doctorName = "{$appointment->doctor->first_name} {$appointment->doctor->last_name}";
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
     * Notify patient when a vaccination is assigned.
     */
    public static function notifyVaccinationAssigned($vaccination)
    {
        if ($vaccination->patient && $vaccination->patient->user) {
            $vaccName = $vaccination->vaccination->name ?? 'Vaccination';
            $dueDateStr = $vaccination->due_date ? \Carbon\Carbon::parse($vaccination->due_date)->format('M d, Y') : 'N/A';
            self::send(
                user: $vaccination->patient->user,
                type: NotificationType::VACCINATION_DUE->value,
                title: 'New Vaccination Assigned',
                message: "A new vaccination dose for {$vaccName} has been assigned. Due date: {$dueDateStr}.",
                category: 'system',
                entityType: 'vaccination',
                entityId: $vaccination->id,
                meta: [
                    'vaccination_name' => $vaccName,
                    'dose_no' => $vaccination->dose_no,
                    'due_date' => $dueDateStr,
                ]
            );
        }
    }

    /**
     * Notify patient when a vaccination is completed.
     */
    public static function notifyVaccinationCompleted($vaccination)
    {
        if ($vaccination->patient && $vaccination->patient->user) {
            $vaccName = $vaccination->vaccination->name ?? 'Vaccination';
            self::send(
                user: $vaccination->patient->user,
                type: NotificationType::VACCINATION_COMPLETED->value,
                title: 'Vaccination Dose Completed',
                message: "Your vaccination dose for {$vaccName} has been marked as completed.",
                category: 'system',
                entityType: 'vaccination',
                entityId: $vaccination->id,
                meta: [
                    'vaccination_name' => $vaccName,
                    'dose_no' => $vaccination->dose_no,
                ]
            );
        }
    }

    /**
     * Notify patient when a vaccination is missed.
     */
    public static function notifyVaccinationMissed($vaccination)
    {
        if ($vaccination->patient && $vaccination->patient->user) {
            $vaccName = $vaccination->vaccination->name ?? 'Vaccination';
            self::send(
                user: $vaccination->patient->user,
                type: NotificationType::VACCINATION_MISSED->value,
                title: 'Vaccination Dose Missed',
                message: "You have missed your scheduled vaccination dose for {$vaccName}.",
                category: 'system',
                entityType: 'vaccination',
                entityId: $vaccination->id,
                meta: [
                    'vaccination_name' => $vaccName,
                    'dose_no' => $vaccination->dose_no,
                ]
            );
        }
    }

    /**
     * Notify patient when a vaccination is overdue.
     */
    public static function notifyVaccinationOverdue($vaccination)
    {
        if ($vaccination->patient && $vaccination->patient->user) {
            $vaccName = $vaccination->vaccination->name ?? 'Vaccination';
            self::send(
                user: $vaccination->patient->user,
                type: NotificationType::VACCINATION_OVERDUE->value,
                title: 'Vaccination Dose Overdue',
                message: "Your vaccination dose for {$vaccName} is now overdue. Please schedule it as soon as possible.",
                category: 'system',
                entityType: 'vaccination',
                entityId: $vaccination->id,
                meta: [
                    'vaccination_name' => $vaccName,
                    'dose_no' => $vaccination->dose_no,
                ]
            );
        }
    }

    /**
     * Notify about patient check-in.
     */
    public static function notifyPatientCheckedIn(Appointment $appointment)
    {
        $dateStr = $appointment->appointment_date instanceof \Carbon\Carbon
            ? $appointment->appointment_date->format('M d, Y')
            : \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y');

        $timeStr = \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A');

        $snapshot = [
            'doctor_name' => "{$appointment->doctor->first_name} {$appointment->doctor->last_name}",
            'patient_name' => "{$appointment->patient->first_name} {$appointment->patient->last_name}",
            'appointment_date' => $dateStr,
            'appointment_time' => $timeStr,
            'consultation_type' => $appointment->consultation_type,
        ];

        // Notify Patient
        if ($appointment->patient && $appointment->patient->user) {
            self::send(
                user: $appointment->patient->user,
                type: NotificationType::PATIENT_CHECKED_IN->value,
                title: 'Checked In Successfully',
                message: "You have checked in for your appointment with {$appointment->doctor->first_name} on {$dateStr} at {$timeStr}.",
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
                type: NotificationType::PATIENT_CHECKED_IN->value,
                title: 'Patient Checked In',
                message: "Patient {$appointment->patient->first_name} has checked in for the appointment on {$dateStr} at {$timeStr}.",
                category: 'appointment',
                entityType: 'appointment',
                entityId: $appointment->id,
                meta: $snapshot
            );
        }
    }

    /**
     * Notify about consultation started.
     */
    public static function notifyConsultationStarted(Appointment $appointment)
    {
        // Notify Patient
        if ($appointment->patient && $appointment->patient->user) {
            self::send(
                user: $appointment->patient->user,
                type: NotificationType::CONSULTATION_STARTED->value,
                title: 'Consultation Started',
                message: "Your consultation with {$appointment->doctor->first_name} has started.",
                category: 'appointment',
                entityType: 'appointment',
                entityId: $appointment->id
            );
        }
    }

    /**
     * Notify about patient skipped.
     */
    public static function notifyPatientSkipped(Appointment $appointment, string $remarks = '')
    {
        // Notify Patient
        if ($appointment->patient && $appointment->patient->user) {
            $message = "Your appointment with {$appointment->doctor->first_name} has been skipped.";
            if ($remarks) {
                $message .= " Reason: {$remarks}";
            }
            self::send(
                user: $appointment->patient->user,
                type: NotificationType::PATIENT_SKIPPED->value,
                title: 'Appointment Skipped',
                message: $message,
                category: 'appointment',
                entityType: 'appointment',
                entityId: $appointment->id
            );
        }
    }

    /**
     * Notify patient that vaccination is due soon.
     */
    public static function notifyVaccinationDue($vaccination, string $timePhrase)
    {
        if ($vaccination->patient && $vaccination->patient->user) {
            $vaccName = $vaccination->vaccination->name ?? 'Vaccination';
            $dueDateStr = $vaccination->due_date ? \Carbon\Carbon::parse($vaccination->due_date)->format('M d, Y') : 'N/A';
            self::send(
                user: $vaccination->patient->user,
                type: NotificationType::VACCINATION_DUE->value,
                title: 'Vaccination Due Reminder',
                message: "Reminder: Your vaccination dose for {$vaccName} is {$timePhrase}. Due date: {$dueDateStr}.",
                category: 'system',
                entityType: 'vaccination',
                entityId: $vaccination->id,
                meta: [
                    'vaccination_name' => $vaccName,
                    'dose_no' => $vaccination->dose_no,
                    'due_date' => $dueDateStr,
                ]
            );
        }
    }

    /**
     * Notify patient to take their medicine.
     */
    public static function notifyMedicineReminder($prescription, string $time)
    {
        if ($prescription->patient && $prescription->patient->user) {
            $medName = $prescription->name ?? 'Medicine';
            $dosage = $prescription->dosage ?? '';
            $msg = "Time to take your medicine: {$medName}" . ($dosage ? " ({$dosage})" : "") . " scheduled for {$time}.";
            
            self::send(
                user: $prescription->patient->user,
                type: NotificationType::MEDICINE_REMINDER->value,
                title: 'Medicine Intake Reminder',
                message: $msg,
                category: 'prescription',
                entityType: 'prescription',
                entityId: $prescription->id,
                meta: [
                    'medicine_name' => $medName,
                    'dosage' => $dosage,
                    'scheduled_time' => $time,
                ]
            );
        }
    }

    /**
     * Notify patient that a diet plan has been assigned to them.
     */
    public static function notifyDietPlanAssigned($plan)
    {
        if ($plan->patient && $plan->patient->user) {
            $doctorName = $plan->doctor 
                ? "{$plan->doctor->first_name} {$plan->doctor->last_name}" 
                : 'Your doctor';
            
            $templateName = $plan->template_name ?? 'Diet Plan';

            self::send(
                user: $plan->patient->user,
                type: NotificationType::DIET_PLAN_ASSIGNED->value,
                title: 'New Diet Plan Assigned',
                message: "{$doctorName} has assigned a new diet plan: {$templateName}.",
                category: 'system',
                entityType: 'diet_plan',
                entityId: $plan->id,
                meta: [
                    'doctor_name' => $doctorName,
                    'template_name' => $templateName,
                    'start_date' => $plan->start_date ? $plan->start_date->format('M d, Y') : '',
                    'duration_days' => $plan->duration_days,
                ]
            );
        }
    }
}