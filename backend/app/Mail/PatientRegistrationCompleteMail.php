<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PatientRegistrationCompleteMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>|null  $appointmentSummary
     */
    public function __construct(
        public string $patientName,
        public string $email,
        public string $passwordNote,
        public ?string $actualPassword = null,
        public ?array $appointmentSummary = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your account and appointment details — ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.patient_registration_complete',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
