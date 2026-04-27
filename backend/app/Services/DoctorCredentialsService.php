<?php

namespace App\Services;

use App\Mail\DoctorCredentialsMail;
use App\Models\Doctor;
use Illuminate\Support\Facades\Mail;

class DoctorCredentialsService
{
    /**
     * Check if doctor has at least one active availability slot
     */
    public function hasActiveAvailability(Doctor $doctor): bool
    {
        return $doctor->availabilities()
            ->where('is_available', true)
            ->exists();
    }

    /**
     * Send credentials email to doctor
     */
    public function sendCredentials(Doctor $doctor, string $password): bool
    {
        if (!$doctor->user?->email) {
            return false;
        }

        try {
            $doctorName = trim($doctor->first_name . ' ' . $doctor->last_name);

            Mail::to($doctor->user->email)->send(
                new DoctorCredentialsMail($doctorName, $doctor->user->email, $password)
            );

            $doctor->email_sent = true;
            $doctor->save();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}