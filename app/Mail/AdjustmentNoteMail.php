<?php

namespace App\Mail;

use App\Mail\Concerns\AttachesUploadedFiles;
use App\Mail\Concerns\HasEmailSignature;
use App\Mail\Concerns\UsesOrganizationMailFrom;
use App\Models\AdjustmentNote;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdjustmentNoteMail extends Mailable
{
    use AttachesUploadedFiles, HasEmailSignature, Queueable, SerializesModels, UsesOrganizationMailFrom;

    public function __construct(
        public AdjustmentNote $note,
        public Organization $organization,
        public ?string $personalMessage = null,
        array $uploadedAttachments = [],
        public ?string $pdfContents = null,
    ) {
        $this->uploadedAttachments = $uploadedAttachments;
    }

    public function envelope(): Envelope
    {
        $label = $this->note->type_label;

        $subject = $this->note->title
            ? __(':label :number — :title', [
                'label' => $label,
                'number' => $this->note->number,
                'title' => $this->note->title,
            ])
            : __(':label :number from :organization', [
                'label' => $label,
                'number' => $this->note->number,
                'organization' => $this->organization->name,
            ]);

        $replyTo = collect([
            $this->organization->email,
            $this->note->creator?->email,
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
            markdown: 'emails.adjustment-notes.sent',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = array_map(function ($file) {
            return Attachment::fromPath($file->getRealPath())
                ->as($file->getClientOriginalName())
                ->withMime($file->getMimeType() ?? 'application/octet-stream');
        }, $this->uploadedAttachments);

        if ($this->pdfContents) {
            $attachments[] = Attachment::fromData(fn () => $this->pdfContents, $this->note->number.'.pdf')
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}
