<?php

namespace App\Mail;

use App\Mail\Concerns\UsesOrganizationMailFrom;
use App\Models\Organization;
use App\Models\Payslip;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayslipMail extends Mailable
{
    use Queueable, SerializesModels, UsesOrganizationMailFrom;

    public function __construct(
        public Payslip $payslip,
        public Organization $organization,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->organizationFrom($this->organization),
            subject: __('Payslip :number from :organization', [
                'number' => $this->payslip->payslip_number,
                'organization' => $this->organization->name,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.payslips.sent',
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        if (! $this->payslip->hasPdf() || ! $this->payslip->pdfExists()) {
            return [];
        }

        return [
            Attachment::fromStorageDisk($this->payslip->pdf_disk, $this->payslip->pdf_path)
                ->as($this->payslip->payslip_number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
