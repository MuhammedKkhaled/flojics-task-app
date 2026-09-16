<?php

namespace App\Mail;

use App\Notifications\Messages\EscalationMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EscalationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly EscalationMessage $escalation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                '[%s] Escalated ticket #%d: %s',
                strtoupper($this->escalation->priority),
                $this->escalation->ticketId,
                $this->escalation->subject,
            ),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.escalation');
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [];
    }
}
