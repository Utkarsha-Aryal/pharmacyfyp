<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SystemStatusMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $title,
        public string $intro,
        public array $lines = [],
    ) {
    }

    // Keep subject setup in one place so all system mails stay consistent.
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    // Use one simple blade so admin test mails and event mails look similar.
    public function content(): Content
    {
        return new Content(
            view: 'emails.system-status',
        );
    }
}
