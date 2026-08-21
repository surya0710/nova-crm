<?php

namespace App\Workflow\Actions;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\CustomerTicket;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use App\Services\CrmEmailService;
use App\Services\CustomerLifecycleService;
use App\Workflow\ActionContext;
use App\Workflow\Contracts\WorkflowActionHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class SendCrmEmailAction implements WorkflowActionHandler
{
    public function __construct(
        protected CrmEmailService $emails,
        protected CustomerLifecycleService $lifecycle,
    ) {}

    /**
     * @param  array<string, mixed>  $configuration
     * @return array<string, mixed>
     */
    public function handle(ActionContext $context, array $configuration): array
    {
        $organization = $context->execution->workflow?->organization
            ?? Organization::query()->find($context->execution->organization_id);

        if (! $organization instanceof Organization) {
            throw ValidationException::withMessages([
                'organization' => 'Workflow organization is required to send email.',
            ]);
        }

        $recipient = $this->resolveRecipientEmail($context->subject, (string) ($configuration['recipient'] ?? 'customer'));
        if ($recipient === null || $recipient === '') {
            throw ValidationException::withMessages([
                'recipient' => 'No email address was found for the selected recipient.',
            ]);
        }

        $input = [
            'email' => $recipient,
            'cc' => $configuration['cc'] ?? null,
            'bcc' => $configuration['bcc'] ?? null,
            'subject' => $configuration['subject'] ?? '',
            'message' => $configuration['message'] ?? '',
            'template_id' => $configuration['template_id'] ?? null,
            'include_signature' => true,
            'idempotency_key' => hash('sha256', $context->execution->id.'|'.$context->action->id),
        ];

        $message = $this->emails->send(
            $organization,
            $context->actor,
            $context->subject,
            $input,
        );

        return [
            'crm_email_message_id' => $message->id,
            'status' => $message->status,
            'to' => $message->to,
        ];
    }

    protected function resolveRecipientEmail(Model $subject, string $type): ?string
    {
        return match ($type) {
            'contact' => $this->contactEmail($subject),
            'record_owner' => $this->recordOwnerEmail($subject),
            default => $this->customerEmail($subject),
        };
    }

    protected function customerEmail(Model $subject): ?string
    {
        if ($subject instanceof Lead) {
            return $this->usableEmail($subject->email);
        }

        if ($subject instanceof Contact) {
            return $this->usableEmail($subject->email) ?? $this->usableEmail($subject->customer?->email);
        }

        $customer = $this->lifecycle->customerFrom($subject);

        return $this->usableEmail($customer?->email)
            ?? $this->usableEmail($customer?->primaryContact?->email)
            ?? $this->contactEmail($subject);
    }

    protected function contactEmail(Model $subject): ?string
    {
        if ($subject instanceof Contact) {
            return $this->usableEmail($subject->email);
        }

        if ($subject instanceof CustomerTicket) {
            return $this->usableEmail($subject->contact?->email)
                ?? $this->usableEmail($subject->customer?->email);
        }

        if ($subject instanceof Customer) {
            return $this->usableEmail($subject->primaryContact?->email)
                ?? $this->usableEmail($subject->email);
        }

        $customer = $this->lifecycle->customerFrom($subject);

        return $this->usableEmail($customer?->primaryContact?->email)
            ?? $this->usableEmail($customer?->email);
    }

    protected function recordOwnerEmail(Model $subject): ?string
    {
        foreach (['assignee', 'creator', 'recorder'] as $relation) {
            if (method_exists($subject, $relation)) {
                $owner = $subject->{$relation};
                if ($owner instanceof User && $this->usableEmail($owner->email)) {
                    return $owner->email;
                }
            }
        }

        $customer = $this->lifecycle->customerFrom($subject);
        if ($customer?->assignee instanceof User) {
            return $this->usableEmail($customer->assignee->email);
        }

        return null;
    }

    protected function usableEmail(?string $email): ?string
    {
        $email = trim((string) $email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}
