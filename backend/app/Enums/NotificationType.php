<?php

namespace App\Enums;

enum NotificationType: string
{
    case APPOINTMENT_BOOKED = 'appointment_booked';
    case APPOINTMENT_CONFIRMED = 'appointment_confirmed';
    case APPOINTMENT_CANCELLED = 'appointment_cancelled';
    case APPOINTMENT_RESCHEDULED = 'appointment_rescheduled';
    case APPOINTMENT_COMPLETED = 'appointment_completed';
    case APPOINTMENT_FAILED = 'appointment_failed';

    case PRESCRIPTION_ADDED = 'prescription_added';
    case DOCTOR_INSTRUCTIONS_ADDED = 'doctor_instructions_added';

    case REVIEW_ADDED = 'review_added';
    case REVIEW_EDITED = 'review_edited';
    case REVIEW_DELETED = 'review_deleted';

    case AVAILABILITY_UPDATED = 'availability_updated';
    case AVAILABILITY_CREATED = 'availability_created';

    case APPOINTMENT_REMINDER_15 = 'appointment_reminder_15';
    case APPOINTMENT_REMINDER_30 = 'appointment_reminder_30';
    case APPOINTMENT_REMINDER_45 = 'appointment_reminder_45';
    case APPOINTMENT_REMINDER_1_HOUR = 'appointment_reminder_1_hour';
    case APPOINTMENT_REMINDER_1_DAY = 'appointment_reminder_1_day';

    case PATIENT_KNOCKS_VIDEO_CALL = 'patient_knocks_video_call';
    case VIDEO_CALL_JOINED = 'video_call_joined';
    case VIDEO_CALL_LEFT = 'video_call_left';
    case VIDEO_CALL_KNOCK_CANCELLED = 'video_call_knock_cancelled';
    case LEAVE_ADDED = 'leave_added';

    case VACCINATION_DUE = 'vaccination_due';
    case VACCINATION_OVERDUE = 'vaccination_overdue';
    case VACCINATION_COMPLETED = 'vaccination_completed';
    case VACCINATION_MISSED = 'vaccination_missed';

    case CONSULTATION_STARTED = 'consultation_started';
    case PATIENT_CHECKED_IN = 'patient_checked_in';
    case DIET_PLAN_ASSIGNED = 'diet_plan_assigned';
    case PATIENT_SKIPPED = 'patient_skipped';
    case MEDICINE_REMINDER = 'medicine_reminder';
}