<?php

namespace App\Mail;

use App\Mail\Concerns\AttachesUploadedFiles;
use App\Mail\Concerns\HasEmailSignature;
use App\Mail\Concerns\UsesOrganizationMailFrom;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CrmMessageMail extends Mailable
{
    use AttachesUploadedFiles, HasEmailSignature, Queueable, SerializesModels, UsesOrganizationMailFrom;

    public function __construct(
        public Organization $organization,
        public string $mailSubject,
        public string $body,
        array $uploadedAttachments = [],
        public ?string $recipientName = null,
    ) {
        $this->uploadedAttachments = $uploadedAttachments;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->organizationFrom($this->organization),
            subject: $this->mailSubject,
            replyTo: $this->organizationReplyTo($this->organization, $this->organization->email),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.crm.message',
        );
    }
}
