<?php

namespace App\Services;

use App\Mail\AdjustmentNoteMail;
use App\Mail\CrmMessageMail;
use App\Mail\CustomerMail;
use App\Mail\InvoiceMail;
use App\Mail\PaymentMail;
use App\Mail\QuotationMail;
use App\Mail\SalesOrderMail;
use App\Models\AdjustmentNote;
use App\Models\CrmEmailMessage;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\SalesOrder;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Storage;

class CrmEmailMailableFactory
{
    public function __construct(
        protected PaymentPdfService $paymentPdf,
        protected AdjustmentNotePdfService $adjustmentNotePdf,
    ) {}

    public function make(CrmEmailMessage $message, Organization $organization): Mailable
    {
        $related = $message->related;
        $attachments = $this->restoreAttachments($message);
        $class = $message->mailable_class;

        if (! $related) {
            return new CrmMessageMail(
                $organization,
                $message->subject,
                (string) $message->body,
                $attachments,
            );
        }

        $mailable = match (true) {
            $class === InvoiceMail::class || $related instanceof Invoice => new InvoiceMail(
                $related,
                $organization,
                $message->body,
                $attachments,
            ),
            $class === QuotationMail::class || $related instanceof Quotation => new QuotationMail(
                $related,
                $organization,
                $message->body,
                $attachments,
            ),
            $class === SalesOrderMail::class || $related instanceof SalesOrder => new SalesOrderMail(
                $related,
                $organization,
                $message->body,
                $attachments,
            ),
            $class === PaymentMail::class || $related instanceof Payment => new PaymentMail(
                $related,
                $organization,
                $message->body,
                $attachments,
                $this->paymentPdf->output($related),
            ),
            $class === AdjustmentNoteMail::class || $related instanceof AdjustmentNote => new AdjustmentNoteMail(
                $related,
                $organization,
                $message->body,
                $attachments,
                $this->adjustmentNotePdf->output($related),
            ),
            $class === CustomerMail::class || $related instanceof Customer => new CustomerMail(
                $related,
                $organization,
                $message->subject,
                $message->body,
                $attachments,
            ),
            default => new CrmMessageMail(
                $organization,
                $message->subject,
                (string) $message->body,
                $attachments,
                $related && isset($related->name) ? (string) $related->name : null,
            ),
        };

        if (property_exists($mailable, 'personalMessage') && filled($message->body)) {
            $mailable->personalMessage = $message->body;
        }

        if (property_exists($mailable, 'mailSubject') && filled($message->subject)) {
            $mailable->mailSubject = $message->subject;
        }

        return $mailable;
    }

    /**
     * @param  array<int, UploadedFile>  $uploads
     * @return list<array{path: string, name: string, mime: string|null}>
     */
    public function storeUploads(CrmEmailMessage $message, array $uploads): array
    {
        $stored = [];

        foreach ($uploads as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('crm-email/'.$message->organization_id.'/'.$message->id, 'local');
            $stored[] = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
            ];
        }

        return $stored;
    }

    /**
     * @return array<int, UploadedFile>
     */
    public function restoreAttachments(CrmEmailMessage $message): array
    {
        $files = [];

        foreach ($message->attachment_paths ?? [] as $stored) {
            $path = $stored['path'] ?? null;
            if (! $path || ! Storage::disk('local')->exists($path)) {
                continue;
            }

            $files[] = new UploadedFile(
                Storage::disk('local')->path($path),
                $stored['name'] ?? basename($path),
                $stored['mime'] ?? null,
                UPLOAD_ERR_OK,
                true,
            );
        }

        return $files;
    }
}
