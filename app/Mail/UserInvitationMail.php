<?php

namespace App\Mail;

use App\Mail\Concerns\UsesOrganizationMailFrom;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels, UsesOrganizationMailFrom;

    public function __construct(
        public User $user,
        public Organization $organization,
        public UserInvitation $invitation,
        public string $acceptUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->organizationFrom($this->organization),
            subject: __('You are invited to join :organization', [
                'organization' => $this->organization->name,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.identity.invitation',
        );
    }
}
