<?php

namespace App\Mail;

use App\Mail\Concerns\UsesOrganizationMailFrom;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserWelcomeMail extends Mailable
{
    use Queueable, SerializesModels, UsesOrganizationMailFrom;

    public function __construct(
        public User $user,
        public Organization $organization,
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->organizationFrom($this->organization),
            subject: __('Welcome to :organization', [
                'organization' => $this->organization->name,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.identity.welcome',
        );
    }
}
