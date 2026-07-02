<?php

namespace App\Jobs;

use App\Mail\AdminBookingAlertMail;
use App\Mail\PatientBookingConfirmationMail;
use App\Mail\TransactionPaidNotificationMail;
use App\Models\Appointment;
use App\Models\EmailLog;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBookingEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 5;

    /**
     * Backoff in seconds between retry attempts.
     * Generous gaps to respect Mailtrap free-tier rate limits.
     */
    public array $backoff = [30, 60, 120, 300];

    public function __construct(
        public string $appointmentId,
        public string $paymentId,
    ) {}

    public function handle(): void
    {
        $attempt = $this->attempts();

        $appointment = Appointment::with(['patient.user', 'doctor.user', 'availability', 'videoConsultation'])
            ->find($this->appointmentId);
        $payment = Payment::find($this->paymentId);

        if (! $appointment || ! $payment) {
            Log::warning('SendBookingEmailJob: appointment or payment not found, skipping.', [
                'appointment_id' => $this->appointmentId,
                'payment_id'     => $this->paymentId,
                'attempt'        => $attempt,
            ]);
            return;
        }

        // Ensure receipt PDF exists before building/sending mailables.
        if ($payment && empty($payment->receipt_pdf)) {
            try {
                $gen = new \App\Jobs\GenerateReceiptJob($payment->id);
                $gen->handle();
                $payment->refresh();
            } catch (\Throwable $e) {
                Log::error('GenerateReceiptJob failed while preparing emails: ' . $e->getMessage(), [
                    'payment_id' => $this->paymentId,
                ]);
                // Don't rethrow — proceed without attachment so email sending still attempts.
            }
        }

        $patientEmail       = $appointment->patient?->email ?? $appointment->patient?->user?->email;
        $adminEmail         = config('mail.admin_notification_email', 'privateopd@cmcludhiana.in');
        $bccEnabled         = config('mail.bcc_enabled', false);
        $bccEmail           = config('mail.bcc_email', 'webclouddeveloper@gmail.com');
        $paymentStatus      = $payment->status instanceof \App\Enums\PaymentStatus
            ? $payment->status->value
            : strtolower((string) $payment->status);
        $isPaid             = $paymentStatus === \App\Enums\PaymentStatus::PAID->value || $paymentStatus === 'paid';
        $transactionEmail   = config('mail.transaction_notification_email', 'telemedicine@cmcludhiana.in');

        // Cache key prefix for deduplication — prevents re-sending already-delivered emails on retry
        $dedupBase = "email_sent:{$this->appointmentId}:{$this->paymentId}";

        $firstError = null;

        // ── 1. Patient confirmation ──────────────────────────────────────────
        if ($patientEmail && ! Cache::has("{$dedupBase}:patient")) {
            $subject = 'Booking Confirmation — Telehealth Deploymeta';
            $mailable = new PatientBookingConfirmationMail($appointment, $payment);
            $htmlBody = null;

            try {
                try {
                    $htmlBody = $mailable->render();
                } catch (\Throwable $e) {
                    Log::warning('PatientBookingConfirmationMail render failed for email log.', [
                        'message' => $e->getMessage(),
                        'appointment_id' => $this->appointmentId,
                        'payment_id' => $this->paymentId,
                    ]);
                }

                $mailer = Mail::to($patientEmail);
                if ($bccEnabled && $bccEmail) {
                    $mailer->bcc($bccEmail);
                }
                $mailer->send($mailable);

                // Mark as sent — keep flag for 48 h so retries skip this
                Cache::put("{$dedupBase}:patient", true, now()->addHours(48));

                EmailLog::recordSent(
                    type: PatientBookingConfirmationMail::class,
                    toEmail: $patientEmail,
                    subject: $subject,
                    appointmentId: $this->appointmentId,
                    paymentId: $this->paymentId,
                    patientId: $appointment->patient_id ?? null,
                    attempt: $attempt,
                    htmlBody: $htmlBody,
                );

                Log::info('PatientBookingConfirmationMail sent.', [
                    'appointment_id' => $this->appointmentId,
                    'to'             => $patientEmail,
                    'attempt'        => $attempt,
                ]);
            } catch (\Throwable $e) {
                EmailLog::recordFailed(
                    type: PatientBookingConfirmationMail::class,
                    toEmail: $patientEmail,
                    subject: $subject,
                    errorMessage: $e->getMessage(),
                    appointmentId: $this->appointmentId,
                    paymentId: $this->paymentId,
                    patientId: $appointment->patient_id ?? null,
                    attempt: $attempt,
                    htmlBody: $htmlBody,
                );

                Log::error('Failed to send PatientBookingConfirmationMail: ' . $e->getMessage(), [
                    'appointment_id' => $this->appointmentId,
                    'payment_id'     => $this->paymentId,
                    'attempt'        => $attempt,
                ]);
                $firstError ??= $e;
            }
        } elseif ($patientEmail && Cache::has("{$dedupBase}:patient")) {
            Log::info('PatientBookingConfirmationMail already sent — skipping on retry.', [
                'appointment_id' => $this->appointmentId,
                'attempt'        => $attempt,
            ]);
        }

        // Pause between emails to respect Mailtrap free-tier rate limit (max 1/s)
        $this->pauseBetweenEmails();

        // ── 2. Admin booking alert ───────────────────────────────────────────
        if ($adminEmail && ! Cache::has("{$dedupBase}:admin")) {
            $subject = 'New Booking Alert — Telehealth Deploymeta';
            $mailable = new AdminBookingAlertMail($appointment, $payment);
            $htmlBody = null;

            try {
                try {
                    $htmlBody = $mailable->render();
                } catch (\Throwable $e) {
                    Log::warning('AdminBookingAlertMail render failed for email log.', [
                        'message' => $e->getMessage(),
                        'appointment_id' => $this->appointmentId,
                        'payment_id' => $this->paymentId,
                    ]);
                }

                $mailer = Mail::to($adminEmail);
                if ($bccEnabled && $bccEmail) {
                    $mailer->bcc($bccEmail);
                }
                $mailer->send($mailable);

                Cache::put("{$dedupBase}:admin", true, now()->addHours(48));

                EmailLog::recordSent(
                    type: AdminBookingAlertMail::class,
                    toEmail: $adminEmail,
                    subject: $subject,
                    appointmentId: $this->appointmentId,
                    paymentId: $this->paymentId,
                    patientId: $appointment->patient_id ?? null,
                    attempt: $attempt,
                    htmlBody: $htmlBody,
                );

                Log::info('AdminBookingAlertMail sent.', [
                    'appointment_id' => $this->appointmentId,
                    'to'             => $adminEmail,
                    'attempt'        => $attempt,
                ]);
            } catch (\Throwable $e) {
                EmailLog::recordFailed(
                    type: AdminBookingAlertMail::class,
                    toEmail: $adminEmail,
                    subject: $subject,
                    errorMessage: $e->getMessage(),
                    appointmentId: $this->appointmentId,
                    paymentId: $this->paymentId,
                    patientId: $appointment->patient_id ?? null,
                    attempt: $attempt,
                    htmlBody: $htmlBody,
                );

                Log::error('Failed to send AdminBookingAlertMail: ' . $e->getMessage(), [
                    'appointment_id' => $this->appointmentId,
                    'payment_id'     => $this->paymentId,
                    'attempt'        => $attempt,
                ]);
                $firstError ??= $e;
            }
        } elseif ($adminEmail && Cache::has("{$dedupBase}:admin")) {
            Log::info('AdminBookingAlertMail already sent — skipping on retry.', [
                'appointment_id' => $this->appointmentId,
                'attempt'        => $attempt,
            ]);
        }

        // ── 3. Transaction paid notification ─────────────────────────────────
        if ($isPaid && $transactionEmail && ! Cache::has("{$dedupBase}:transaction")) {
            $this->pauseBetweenEmails();

            $subject = 'Payment Received — Telehealth Deploymeta';
            $mailable = new TransactionPaidNotificationMail($appointment, $payment);
            $htmlBody = null;

            try {
                try {
                    $htmlBody = $mailable->render();
                } catch (\Throwable $e) {
                    Log::warning('TransactionPaidNotificationMail render failed for email log.', [
                        'message' => $e->getMessage(),
                        'appointment_id' => $this->appointmentId,
                        'payment_id' => $this->paymentId,
                    ]);
                }

                $mailer = Mail::to($transactionEmail);
                if ($bccEnabled && $bccEmail) {
                    $mailer->bcc($bccEmail);
                }
                $mailer->send($mailable);

                Cache::put("{$dedupBase}:transaction", true, now()->addHours(48));

                EmailLog::recordSent(
                    type: TransactionPaidNotificationMail::class,
                    toEmail: $transactionEmail,
                    subject: $subject,
                    appointmentId: $this->appointmentId,
                    paymentId: $this->paymentId,
                    patientId: $appointment->patient_id ?? null,
                    attempt: $attempt,
                    htmlBody: $htmlBody,
                );

                Log::info('TransactionPaidNotificationMail sent.', [
                    'appointment_id' => $this->appointmentId,
                    'to'             => $transactionEmail,
                    'attempt'        => $attempt,
                ]);
            } catch (\Throwable $e) {
                EmailLog::recordFailed(
                    type: TransactionPaidNotificationMail::class,
                    toEmail: $transactionEmail,
                    subject: $subject,
                    errorMessage: $e->getMessage(),
                    appointmentId: $this->appointmentId,
                    paymentId: $this->paymentId,
                    patientId: $appointment->patient_id ?? null,
                    attempt: $attempt,
                    htmlBody: $htmlBody,
                );

                Log::error('Failed to send TransactionPaidNotificationMail: ' . $e->getMessage(), [
                    'appointment_id' => $this->appointmentId,
                    'payment_id'     => $this->paymentId,
                    'attempt'        => $attempt,
                ]);
                $firstError ??= $e;
            }
        } elseif ($isPaid && $transactionEmail && Cache::has("{$dedupBase}:transaction")) {
            Log::info('TransactionPaidNotificationMail already sent — skipping on retry.', [
                'appointment_id' => $this->appointmentId,
                'attempt'        => $attempt,
            ]);
        }

        // Re-throw so the queue driver retries with the configured backoff.
        if ($firstError !== null) {
            throw $firstError;
        }
    }

    /**
     * Sleep 3 seconds between each email send to stay well below
     * Mailtrap sandbox's 1 email/second limit. SMTP handshaking itself
     * takes ~1 s, so a 3 s sleep gives ≥ 4 s total gap — safe margin.
     */
    private function pauseBetweenEmails(): void
    {
        sleep(3);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendBookingEmailJob permanently failed after all retries.', [
            'appointment_id' => $this->appointmentId,
            'payment_id'     => $this->paymentId,
            'message'        => $exception->getMessage(),
        ]);
    }
}