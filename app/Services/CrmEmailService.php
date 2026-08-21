<?php

namespace App\Services;

use App\Jobs\SendCrmEmailJob;
use App\Mail\CrmMessageMail;
use App\Models\AdjustmentNote;
use App\Models\Contact;
use App\Models\CrmEmailMessage;
use App\Models\CrmEmailTemplate;
use App\Models\Customer;
use App\Models\CustomerTicket;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;

class CrmEmailService
{
    public function __construct(
        protected OrganizationMailer $mailer,
        protected OrganizationMailConfig $mailConfig,
        protected CrmEmailVariableRenderer $renderer,
        protected CrmEmailConversationService $conversations,
        protected CrmEmailMailableFactory $mailables,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @param  array<int, \Illuminate\Http\UploadedFile>  $attachments
     */
    public function send(
        Organization $organization,
        User $sender,
        Model $related,
        array $input,
        ?Mailable $mailable = null,
        array $attachments = [],
        bool $ccSender = false,
    ): CrmEmailMessage {
        if (! $this->mailer->isConfigured($organization)) {
            throw new \RuntimeException(__('Configure organization email in Settings → Email before sending.'));
        }

        $config = $this->mailConfig->for($organization);
        $context = $this->renderer->contextFor($organization, $related);
        $template = $this->resolveTemplate($organization, $input['template_id'] ?? null);

        $subject = $this->renderer->interpolate(
            filled($input['subject'] ?? null) ? (string) $input['subject'] : (string) ($template?->subject ?? ''),
            $context,
        );
        $body = $this->renderer->interpolate(
            filled($input['message'] ?? null) ? (string) $input['message'] : (string) ($template?->body ?? ''),
            $context,
        );

        $recipients = ClientEmailCc::resolve(
            $sender,
            $input['email'] ?? '',
            $input['cc'] ?? null,
            $input['bcc'] ?? null,
            $config->defaultCc(),
            $config->defaultBcc(),
            $ccSender,
        );

        if ($recipients['to'] === []) {
            throw new \RuntimeException(__('Enter at least one valid recipient email address.'));
        }

        $idempotencyKey = filled($input['idempotency_key'] ?? null)
            ? (string) $input['idempotency_key']
            : null;

        if ($idempotencyKey !== null) {
            $existing = CrmEmailMessage::query()
                ->where('organization_id', $organization->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $mailable ??= new CrmMessageMail(
            $organization,
            $subject !== '' ? $subject : __('Message from :name', ['name' => $organization->name]),
            $body,
            $attachments,
            $this->recipientName($related),
        );

        [$customer, $contact] = $this->customerAndContact($related, $recipients['to']);
        $rfcId = $this->conversations->generateRfcMessageId((int) $organization->id);
        $inReplyTo = trim((string) ($input['in_reply_to'] ?? ''), " \t\n\r\0\x0B<>");
        $from = $config->fromAddress();

        try {
            $message = CrmEmailMessage::query()->create([
            'organization_id' => $organization->id,
            'related_type' => $related->getMorphClass(),
            'related_id' => $related->getKey(),
            'customer_id' => $customer?->id,
            'contact_id' => $contact?->id,
            'template_id' => $template?->id,
            'to' => $recipients['to'],
            'cc' => $recipients['cc'] ?: null,
            'bcc' => $recipients['bcc'] ?: null,
            'subject' => $subject !== '' ? $subject : $this->fallbackSubject($related, $organization),
            'body' => $body !== '' ? $body : null,
            'attachments' => $this->attachmentNames($attachments),
            'status' => 'queued',
            'provider' => $config->provider(),
            'rfc_message_id' => $rfcId,
            'in_reply_to' => $inReplyTo !== '' ? $inReplyTo : null,
            'references_header' => $this->referencesHeader($input, $rfcId),
            'thread_id' => filled($input['thread_id'] ?? null) ? (string) $input['thread_id'] : null,
            'mailable_class' => $mailable::class,
            'direction' => 'outbound',
            'from_email' => $from?->address,
            'from_name' => $from?->name,
            'queued_at' => now(),
            'sent_by' => $sender->id,
            'idempotency_key' => $idempotencyKey,
        ]);
        } catch (UniqueConstraintViolationException $e) {
            if ($idempotencyKey !== null) {
                $existing = CrmEmailMessage::query()
                    ->where('organization_id', $organization->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            throw $e;
        }

        $message->attachment_paths = $this->mailables->storeUploads($message, $attachments);
        $message->save();

        $this->conversations->assign($message->fresh());

        Log::info('crm.email.queued', [
            'message_id' => $message->id,
            'organization_id' => $organization->id,
            'related_type' => $related->getMorphClass(),
            'related_id' => $related->getKey(),
        ]);

        SendCrmEmailJob::dispatch($message->id);

        $message = $message->fresh();

        if ($message && $message->status === 'failed') {
            throw new \RuntimeException($message->error_message ?: __('Failed to send email.'));
        }

        return $message;
    }

    protected function resolveTemplate(Organization $organization, mixed $templateId): ?CrmEmailTemplate
    {
        $id = (int) $templateId;

        if ($id < 1) {
            return null;
        }

        return CrmEmailTemplate::query()
            ->where('organization_id', $organization->id)
            ->active()
            ->find($id);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function referencesHeader(array $input, string $rfcId): ?string
    {
        $parts = [];
        $inReplyTo = trim((string) ($input['in_reply_to'] ?? ''), " \t\n\r\0\x0B<>");
        $existing = trim((string) ($input['references'] ?? ''));

        if ($existing !== '') {
            $parts[] = $existing;
        } elseif ($inReplyTo !== '') {
            $parts[] = '<'.$inReplyTo.'>';
        }

        $parts[] = '<'.trim($rfcId, '<>').'>';

        $header = trim(implode(' ', $parts));

        return $header !== '' ? $header : null;
    }

    /**
     * @param  list<string>  $to
     * @return array{0: ?Customer, 1: ?Contact}
     */
    protected function customerAndContact(Model $related, array $to): array
    {
        $customer = null;
        $contact = null;

        if ($related instanceof Customer) {
            $customer = $related;
        } elseif ($related instanceof Contact) {
            $contact = $related;
            $customer = $related->customer;
        } elseif ($related instanceof Opportunity) {
            $customer = $related->customer;
        } elseif ($related instanceof CustomerTicket) {
            $customer = $related->customer;
            $contact = $related->contact;
        } elseif ($related instanceof Lead && method_exists($related, 'convertedCustomer')) {
            $customer = $related->convertedCustomer ?? null;
        } elseif (method_exists($related, 'customer')) {
            $customer = $related->customer;
        }

        if ($customer && ! $contact && $to !== []) {
            $contact = $customer->contacts()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($to[0])])
                ->first();
        }

        return [$customer, $contact];
    }

    protected function fallbackSubject(Model $related, Organization $organization): string
    {
        return match (true) {
            $related instanceof Quotation => __('Quotation :number', ['number' => $related->number]),
            $related instanceof SalesOrder => __('Sales order :number', ['number' => $related->number]),
            $related instanceof Invoice => __('Invoice :number', ['number' => $related->number]),
            $related instanceof Payment => __('Payment receipt :number', ['number' => $related->number]),
            $related instanceof AdjustmentNote => __(':label :number', ['label' => $related->type_label, 'number' => $related->number]),
            $related instanceof CustomerTicket => __('Ticket :number', ['number' => $related->number]),
            $related instanceof Opportunity => $related->title,
            $related instanceof Contact => __('Message to :name', ['name' => $related->name]),
            $related instanceof Customer => __('Message to :name', ['name' => $related->display_name]),
            default => __('Message from :name', ['name' => $organization->name]),
        };
    }

    protected function recipientName(Model $related): ?string
    {
        if ($related instanceof Contact) {
            return $related->name;
        }

        if ($related instanceof Customer) {
            return $related->name;
        }

        if (method_exists($related, 'customer')) {
            return $related->customer?->name;
        }

        return null;
    }

    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $attachments
     * @return list<string>
     */
    protected function attachmentNames(array $attachments): array
    {
        return collect($attachments)
            ->filter()
            ->map(fn ($file) => $file->getClientOriginalName())
            ->values()
            ->all();
    }
}
