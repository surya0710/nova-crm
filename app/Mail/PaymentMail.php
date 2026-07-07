<?php

namespace App\Mail;

use App\Mail\Concerns\AttachesUploadedFiles;
use App\Mail\Concerns\UsesOrganizationMailFrom;
use App\Models\Organization;
use App\Models\Payment;
use App\Services\OrganizationTerminology;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentMail extends Mailable
{
    use AttachesUploadedFiles, Queueable, SerializesModels, UsesOrganizationMailFrom;

    public function __construct(
        public Payment $payment,
        public Organization $organization,
        public ?string $personalMessage = null,
        array $uploadedAttachments = [],
    ) {
        $this->uploadedAttachments = $uploadedAttachments;
    }

    public function envelope(): Envelope
    {
        $paymentLabel = app(OrganizationTerminology::class)->get('payment', $this->organization);

        $subject = __(':label receipt :number from :organization', [
            'label' => $paymentLabel,
            'number' => $this->payment->number,
            'organization' => $this->organization->name,
        ]);

        $replyTo = collect([
            $this->organization->email,
            $this->payment->recorder?->email,
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
            markdown: 'emails.payments.receipt',
        );
    }
}
