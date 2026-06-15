<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PatientNextAppointmentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Patient $patient,
        public ?Appointment $previousAppointment,
        public array $nextSlot,
        public ?string $adminMessage = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Appointment Slot Details - '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.patient_next_appointment',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
