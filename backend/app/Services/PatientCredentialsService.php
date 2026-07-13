<?php

namespace App\Services;

use App\Mail\PatientCredentialsMail;
use App\Models\EmailLog;
use App\Models\Patient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PatientCredentialsService
{
    public function sendCredentials(Patient $patient, string $password): bool
    {
        $email = $patient->user?->email ?: $patient->email;

        if (! $email) {
            return false;
        }

        $patientName = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''));
        $patientName = $patientName !== '' ? $patientName : ($patient->user?->name ?? 'Patient');

        $mailable = new PatientCredentialsMail($patientName, $email, $password);
        $subject = 'Your Account Credentials - ' . config('app.name');
        $htmlBody = null;

        try {
            $htmlBody = $mailable->render();
        } catch (\Throwable $e) {
            // Ignore render failures for logging fallback.
        }

        try {
            Mail::to($email)->send($mailable);

            EmailLog::recordSent(
                type: PatientCredentialsMail::class,
                toEmail: $email,
                subject: $subject,
                patientId: $patient->id,
                htmlBody: $htmlBody,
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send patient credentials email: ' . $e->getMessage(), [
                'patient_id' => $patient->id,
                'email' => $email,
            ]);

            EmailLog::recordFailed(
                type: PatientCredentialsMail::class,
                toEmail: $email,
                subject: $subject,
                errorMessage: $e->getMessage(),
                patientId: $patient->id,
                htmlBody: $htmlBody,
            );

            return false;
        }
    }
}
