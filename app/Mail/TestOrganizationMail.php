<?php

namespace App\Mail;

use App\Mail\Concerns\UsesOrganizationMailFrom;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestOrganizationMail extends Mailable
{
    use Queueable, SerializesModels, UsesOrganizationMailFrom;

    public function __construct(public Organization $organization) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->organizationFrom($this->organization),
            subject: __('Test email from :name', ['name' => $this->organization->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.organizations.test',
        );
    }
}
