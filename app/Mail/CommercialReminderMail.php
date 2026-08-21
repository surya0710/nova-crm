<?php

namespace App\Mail;

use App\Mail\Concerns\UsesOrganizationMailFrom;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommercialReminderMail extends Mailable
{
    use Queueable, SerializesModels, UsesOrganizationMailFrom;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public Organization $organization,
        public string $template,
        public string $emailSubject,
        public array $payload = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->organizationFrom($this->organization),
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.commercial.'.$this->template,
            with: [
                'organization' => $this->organization,
                ...$this->payload,
            ],
        );
    }
}
