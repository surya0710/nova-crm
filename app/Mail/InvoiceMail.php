<?php

namespace App\Mail;

use App\Mail\Concerns\AttachesUploadedFiles;
use App\Mail\Concerns\UsesOrganizationMailFrom;
use App\Models\Invoice;
use App\Models\Organization;
use App\Services\OrganizationTerminology;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use AttachesUploadedFiles, Queueable, SerializesModels, UsesOrganizationMailFrom;

    public function __construct(
        public Invoice $invoice,
        public Organization $organization,
        public ?string $personalMessage = null,
        array $uploadedAttachments = [],
    ) {
        $this->uploadedAttachments = $uploadedAttachments;
    }

    public function envelope(): Envelope
    {
        $invoiceLabel = app(OrganizationTerminology::class)->get('invoice', $this->organization);

        $subject = $this->invoice->title
            ? __(':label :number — :title', [
                'label' => $invoiceLabel,
                'number' => $this->invoice->number,
                'title' => $this->invoice->title,
            ])
            : __(':label :number from :organization', [
                'label' => $invoiceLabel,
                'number' => $this->invoice->number,
                'organization' => $this->organization->name,
            ]);

        $replyTo = collect([
            $this->organization->email,
            $this->invoice->creator?->email,
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
            markdown: 'emails.invoices.sent',
        );
    }
}
