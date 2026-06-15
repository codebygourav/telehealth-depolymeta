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

class TransactionPaidNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Appointment $appointment,
        public Payment $payment
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $patientName = $this->appointment->patient 
            ? trim($this->appointment->patient->first_name . ' ' . $this->appointment->patient->last_name)
            : 'Patient';

        return new Envelope(
            subject: 'Transaction Verified (PAID) - ' . $patientName . ' — ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.transaction_paid_alert',
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

        if ($this->payment) {
            if (!$this->payment->receipt_pdf) {
                try {
                    $job = new \App\Jobs\GenerateReceiptJob($this->payment->id);
                    $job->handle();
                    $this->payment->refresh();
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('On-the-fly receipt generation in TransactionPaidNotificationMail failed: ' . $e->getMessage());
                }
            }

            if ($this->payment->receipt_pdf) {
                $pdfPath = storage_path('app/public/' . $this->payment->receipt_pdf);
                if (file_exists($pdfPath)) {
                    $attachments[] = Attachment::fromPath($pdfPath)
                        ->as('Receipt_' . $this->appointment->slug . '.pdf')
                        ->withMime('application/pdf');
                }
            }
        }

        return $attachments;
    }
}
