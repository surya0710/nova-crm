<?php

namespace App\Mail;

use App\Mail\Concerns\AttachesUploadedFiles;
use App\Mail\Concerns\HasEmailSignature;
use App\Mail\Concerns\UsesOrganizationMailFrom;
use App\Models\Organization;
use App\Models\SalesOrder;
use App\Services\OrganizationTerminology;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SalesOrderMail extends Mailable
{
    use AttachesUploadedFiles, HasEmailSignature, Queueable, SerializesModels, UsesOrganizationMailFrom;

    public function __construct(
        public SalesOrder $salesOrder,
        public Organization $organization,
        public ?string $personalMessage = null,
        array $uploadedAttachments = [],
    ) {
        $this->uploadedAttachments = $uploadedAttachments;
    }

    public function envelope(): Envelope
    {
        $orderLabel = app(OrganizationTerminology::class)->get('sales_order', $this->organization);

        $subject = $this->salesOrder->title
            ? __(':label :number — :title', [
                'label' => $orderLabel,
                'number' => $this->salesOrder->number,
                'title' => $this->salesOrder->title,
            ])
            : __(':label :number from :organization', [
                'label' => $orderLabel,
                'number' => $this->salesOrder->number,
                'organization' => $this->organization->name,
            ]);

        $replyTo = collect([
            $this->organization->email,
            $this->salesOrder->creator?->email,
        ])->filter()->first();

        return new Envelope(
            from: $this->organizationFrom($this->organization),
            subject: $subject,
            replyTo: $this->organizationReplyTo($this->organization, $replyTo),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.sales-orders.sent',
        );
    }
}
