<?php

namespace App\Models;

use App\Mail\AdminBookingAlertMail;
use App\Mail\PatientBookingConfirmationMail;
use App\Mail\TransactionPaidNotificationMail;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class EmailLog extends Model
{
    protected $fillable = [
        'type',
        'to_email',
        'subject',
        'patient_id',
        'appointment_id',
        'payment_id',
        'status',
        'error_message',
        'attempt',
        'sent_at',
        'failed_at',
        'html_body',
    ];

    protected $casts = [
        'sent_at'   => 'datetime',
        'failed_at' => 'datetime',
    ];

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function getPatientNameAttribute(): ?string
    {
        $patient = $this->appointment?->patient ?? $this->payment?->appointment?->patient ?? null;
        if (! $patient) {
            return null;
        }

        $name = trim(trim($patient->first_name . ' ' . $patient->last_name));

        return $name ?: ($patient->user?->name ?? $patient->email ?? null);
    }

    public function getPatientUnitIdAttribute(): ?string
    {
        $patient = $this->appointment?->patient ?? $this->payment?->appointment?->patient ?? null;

        return $patient?->patient_unit_number
            ?? $patient?->unit_number
            ?? $patient?->unit_id
            ?? null;
    }

    public function getPatientLineAttribute(): ?string
    {
        if (! $name = $this->patient_name) {
            return null;
        }

        $parts = [];
        if ($unitId = $this->patient_unit_id) {
            $parts[] = sprintf('unit id: %s', $unitId);
        }

        if ($existingId = $this->patient_existing_id) {
            $parts[] = sprintf('Unit ID: %s', $existingId);
        }

        return $parts
            ? sprintf('%s (%s)', $name, implode(', ', $parts))
            : $name;
    }

    public function getPatientExistingIdAttribute(): ?string
    {
        $patient = $this->appointment?->patient ?? $this->payment?->appointment?->patient ?? null;

        return $patient?->existing_patient_id;
    }

    public function getRecipientWithPatientLineAttribute(): string
    {
        $html = e($this->to_email);

        if ($this->patient_line) {
            $html .= '<br><span class="text-xs text-gray-500">' . e($this->patient_line) . '</span>';
        }

        return $html;
    }

    public function getRenderedHtmlBodyAttribute(): ?string
    {
        if ($this->html_body) {
            return $this->sanitizeEmailBody($this->html_body);
        }

        $appointment = $this->appointment ?? $this->payment?->appointment;
        if (! $appointment || ! class_exists($this->type)) {
            return null;
        }

        try {
            $mailable = match ($this->type) {
                PatientBookingConfirmationMail::class => new PatientBookingConfirmationMail($appointment, $this->payment),
                AdminBookingAlertMail::class => new AdminBookingAlertMail($appointment, $this->payment),
                TransactionPaidNotificationMail::class => new TransactionPaidNotificationMail($appointment, $this->payment),
                default => null,
            };

            return $mailable?->render() ? $this->sanitizeEmailBody($mailable->render()) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function sanitizeEmailBody(string $html): string
    {
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
            $html = $matches[1];
        }

        return trim($html);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Friendly display name for the email type (strips namespace, trailing 'Mail').
     */
    public function getTypeDisplayNameAttribute(): string
    {
        $short = class_basename($this->type);
        $short = preg_replace('/Mail$/', '', $short);
        // Convert CamelCase → "Camel Case"
        return preg_replace('/([A-Z])/', ' $1', $short);
    }

    // ── Static helpers ───────────────────────────────────────────────────────

    public static function recordSent(string $type, string $toEmail, string $subject, ?string $appointmentId = null, ?string $paymentId = null, ?string $patientId = null, int $attempt = 1, ?string $htmlBody = null): self
    {
        return static::create([
            'type'           => $type,
            'to_email'       => $toEmail,
            'subject'        => $subject,
            'appointment_id' => $appointmentId,
            'payment_id'     => $paymentId,
            'patient_id'     => $patientId,
            'status'         => 'sent',
            'attempt'        => $attempt,
            'sent_at'        => now(),
            'html_body'      => $htmlBody,
        ]);
    }

    public static function recordFailed(string $type, string $toEmail, string $subject, string $errorMessage, ?string $appointmentId = null, ?string $paymentId = null, ?string $patientId = null, int $attempt = 1, ?string $htmlBody = null): self
    {
        return static::create([
            'type'          => $type,
            'to_email'      => $toEmail,
            'subject'       => $subject,
            'appointment_id' => $appointmentId,
            'payment_id'     => $paymentId,
            'patient_id'     => $patientId,
            'status'         => 'failed',
            'error_message'  => $errorMessage,
            'attempt'        => $attempt,
            'failed_at'      => now(),
            'html_body'      => $htmlBody,
        ]);
    }

    // ── Stats helpers ────────────────────────────────────────────────────────

    public static function dailyStats(int $days = 7): array
    {
        $stats = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $sent   = static::where('status', 'sent')->whereDate('created_at', $date)->count();
            $failed = static::where('status', 'failed')->whereDate('created_at', $date)->count();
            $stats[] = compact('date', 'sent', 'failed');
        }
        return $stats;
    }
}
