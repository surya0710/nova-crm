<?php

namespace App\Mail;

use App\Mail\Concerns\AttachesUploadedFiles;
use App\Mail\Concerns\HasEmailSignature;
use App\Mail\Concerns\UsesOrganizationMailFrom;
use App\Models\Customer;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerMail extends Mailable
{
    use AttachesUploadedFiles, HasEmailSignature, Queueable, SerializesModels, UsesOrganizationMailFrom;

    public function __construct(
        public Customer $customer,
        public Organization $organization,
        public string $mailSubject,
        public ?string $personalMessage = null,
        array $uploadedAttachments = [],
    ) {
        $this->uploadedAttachments = $uploadedAttachments;
    }

    public function envelope(): Envelope
    {
        $replyTo = collect([
            $this->organization->email,
        ])->filter()->first();

        return new Envelope(
            from: $this->organizationFrom($this->organization),
            subject: $this->mailSubject,
            replyTo: $this->organizationReplyTo($this->organization, $replyTo),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.customers.message',
        );
    }
}
