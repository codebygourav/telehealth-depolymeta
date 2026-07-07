<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PatientBookingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Appointment $appointment,
        public ?Payment $payment = null
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $doctorName = $this->appointment->doctor
            ? trim($this->appointment->doctor->first_name . ' ' . $this->appointment->doctor->last_name)
            : 'Doctor';

        $dateStr = $this->appointment->appointment_date instanceof \Carbon\Carbon
            ? $this->appointment->appointment_date->format('M d, Y')
            : ($this->appointment->appointment_date ? \Carbon\Carbon::parse($this->appointment->appointment_date)->format('M d, Y') : '');

        return new Envelope(
            subject: 'Appointment Confirmed - with ' . $doctorName . ' on ' . $dateStr . ' — ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.patient_booking_confirmed',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->payment && $this->payment->receipt_pdf) {
            $pdfPath = storage_path('app/public/' . $this->payment->receipt_pdf);
            if (file_exists($pdfPath)) {
                $attachments[] = Attachment::fromPath($pdfPath)
                    ->as('Receipt_' . $this->appointment->slug . '.pdf')
                    ->withMime('application/pdf');
            }
        }

        return $attachments;
    }
}