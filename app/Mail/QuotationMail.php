<?php

namespace App\Mail;

use App\Mail\Concerns\AttachesUploadedFiles;
use App\Mail\Concerns\UsesOrganizationMailFrom;
use App\Models\Organization;
use App\Models\Quotation;
use App\Services\OrganizationTerminology;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationMail extends Mailable
{
    use AttachesUploadedFiles, Queueable, SerializesModels, UsesOrganizationMailFrom;

    public function __construct(
        public Quotation $quotation,
        public Organization $organization,
        public ?string $personalMessage = null,
        array $uploadedAttachments = [],
    ) {
        $this->uploadedAttachments = $uploadedAttachments;
    }

    public function envelope(): Envelope
    {
        $quotationLabel = app(OrganizationTerminology::class)->get('quotation', $this->organization);

        $subject = $this->quotation->title
            ? __(':label :number — :title', [
                'label' => $quotationLabel,
                'number' => $this->quotation->number,
                'title' => $this->quotation->title,
            ])
            : __(':label :number from :organization', [
                'label' => $quotationLabel,
                'number' => $this->quotation->number,
                'organization' => $this->organization->name,
            ]);

        $replyTo = collect([
            $this->organization->email,
            $this->quotation->creator?->email,
        ])->filter()->first();

        return new Envelope(
            from: $this->organizationFrom($this->organization),
            subject: $subject,
            replyTo: $replyTo
                ? [new Address($replyTo, $this->organization->name)]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.quotations.sent',
        );
    }
}
