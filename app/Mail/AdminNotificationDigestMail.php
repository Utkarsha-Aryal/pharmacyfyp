<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNotificationDigestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public array $notifications,
        public string $mailSubject = 'Admin notification digest',
    ) {
    }

    // Keep the mail subject here so the notification command stays short and readable.
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    // This uses one clean Blade so alert emails stay easy to scan in inbox.
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-notification-digest',
        );
    }
}
