<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Patient;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PatientNotificationController extends Controller
{
    /**
     * Send an appointment reminder notification for the next upcoming booking.
     */
    public function notifyNextAppointment(Request $request, Patient $patient)
    {
        $appointment = $patient->appointments()
            ->whereIn('status', [
                AppointmentStatus::PENDING->value,
                AppointmentStatus::CONFIRMED->value,
            ])
            ->whereDate('appointment_date', '>=', now()->toDateString())
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->with(['doctor.user', 'patient.user'])
            ->first();

        if (! $appointment) {
            return back()->with('patient_notification_error', 'No upcoming appointment found to notify.');
        }

        // Choose the closest reminder tier based on remaining time.
        $appointmentDateTime = Carbon::parse($appointment->appointment_date->toDateString() . ' ' . $appointment->appointment_time);
        $minutesUntil = (int) now()->diffInMinutes($appointmentDateTime, false);

        // If appointment time is in the past (edge case), fall back to 15 minutes.
        if ($minutesUntil < 0) {
            $minutesUntil = 15;
        }

        $reminderMinutes = match (true) {
            $minutesUntil <= 20 => 15,
            $minutesUntil <= 35 => 30,
            $minutesUntil <= 55 => 45,
            $minutesUntil <= 90 => 60,
            default => 1440, // 1 day
        };

        NotificationService::notifyAppointmentReminder($appointment, $reminderMinutes);

        return back()->with('patient_notification_sent', true);
    }
}

