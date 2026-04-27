<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DoctorCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $doctorName;
    public $email;
    public $password;

    /**
     * Create a new message instance.
     */
    public function __construct($doctorName, $email, $password)
    {
        $this->doctorName = $doctorName;
        $this->email = $email;
        $this->password = $password;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Doctor Portal Credentials',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.DoctorCredentialsTemplate',
        );
    }

    /**
     * Attachments.
     */
    public function attachments(): array
    {
        return [];
    }
}
