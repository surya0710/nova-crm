<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\CrmActivity;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CrmActivityService
{
    public const SORTABLE = ['occurred_at', 'due_at', 'created_at', 'priority', 'status'];

    public function __construct(protected CustomerService $customers) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForContact(Contact $contact, array $data, User $actor): CrmActivity
    {
        return $this->create([
            ...$data,
            'organization_id' => $contact->organization_id,
            'customer_id' => $contact->customer_id,
            'contact_id' => $contact->id,
        ], $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForCustomer(Customer $customer, array $data, User $actor): CrmActivity
    {
        return $this->create([
            ...$data,
            'organization_id' => $customer->organization_id,
            'customer_id' => $customer->id,
            'contact_id' => $data['contact_id'] ?? null,
        ], $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForOpportunity(Opportunity $opportunity, array $data, User $actor): CrmActivity
    {
        return $this->create([
            ...$data,
            'organization_id' => $opportunity->organization_id,
            'customer_id' => $opportunity->customer_id,
            'opportunity_id' => $opportunity->id,
            'contact_id' => $data['contact_id'] ?? null,
        ], $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logEmail(
        Customer $customer,
        User $actor,
        string $subject,
        string $body,
        ?string $recipient = null,
        ?Contact $contact = null,
        array $metadata = [],
    ): CrmActivity {
        if (! $contact && $recipient) {
            $contact = $customer->contacts()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($recipient)])
                ->first();
        }

        return $this->create([
            'organization_id' => $customer->organization_id,
            'customer_id' => $customer->id,
            'contact_id' => $contact?->id,
            'type' => 'email',
            'subject' => $subject,
            'body' => $body,
            'occurred_at' => now(),
            'direction' => 'outbound',
            'outcome' => 'sent',
            'status' => 'completed',
            'metadata' => array_filter([
                'recipient' => $recipient,
                ...$metadata,
            ], fn ($value) => $value !== null && $value !== [] && $value !== ''),
        ], $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): CrmActivity
    {
        $type = (string) ($data['type'] ?? '');
        if (! array_key_exists($type, config('crm_activities.types', []))) {
            throw ValidationException::withMessages(['type' => __('The selected activity type is invalid.')]);
        }

        $subject = trim((string) ($data['subject'] ?? ''));
        if ($subject === '') {
            throw ValidationException::withMessages(['subject' => __('A subject is required.')]);
        }

        $openTypes = config('crm_activities.open_types', ['task', 'follow_up']);
        $status = $data['status'] ?? (in_array($type, $openTypes, true) && empty($data['completed_at']) ? 'open' : 'completed');
        if ($status === 'completed' && empty($data['completed_at'])) {
            $data['completed_at'] = $data['occurred_at'] ?? now();
        }

        $activity = DB::transaction(function () use ($data, $actor, $type, $subject, $status) {
            return CrmActivity::query()->create([
                'organization_id' => $data['organization_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'contact_id' => $data['contact_id'] ?? null,
                'opportunity_id' => $data['opportunity_id'] ?? null,
                'type' => $type,
                'subject' => $subject,
                'body' => filled($data['body'] ?? null) ? trim((string) $data['body']) : null,
                'occurred_at' => $data['occurred_at'] ?? now(),
                'due_at' => $data['due_at'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'direction' => $data['direction'] ?? ($this->defaultDirection($type)),
                'outcome' => $data['outcome'] ?? null,
                'status' => $status,
                'priority' => $data['priority'] ?? 'medium',
                'completed_at' => $data['completed_at'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? $actor->id,
                'created_by' => $actor->id,
                'metadata' => $data['metadata'] ?? null,
            ]);
        });

        if ($activity->customer_id) {
            $this->customers->touchLastActivity(
                Customer::query()->find($activity->customer_id) ?? $activity->customer,
                $activity->occurred_at,
            );
        }

        if ($activity->opportunity_id) {
            app(OpportunityService::class)->syncNextActivity($activity->opportunity ?? Opportunity::query()->find($activity->opportunity_id));
        }

        return $activity->fresh(['creator', 'assignee', 'contact', 'customer', 'opportunity']);
    }

    public function complete(CrmActivity $activity, User $actor): CrmActivity
    {
        $activity->update([
            'status' => 'completed',
            'completed_at' => $activity->completed_at ?? now(),
        ]);

        if ($activity->opportunity_id) {
            app(OpportunityService::class)->syncNextActivity($activity->opportunity ?? $activity->fresh()->opportunity);
        }

        return $activity->fresh();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function applyIndexFilters(Builder $query, array $filters, ?User $user = null): Builder
    {
        $scope = (string) ($filters['scope'] ?? 'all');
        match ($scope) {
            'mine' => $user ? $query->mine($user->id) : $query,
            'upcoming' => $query->upcoming(),
            'overdue' => $query->overdue(),
            'completed' => $query->completed(),
            'open' => $query->where('status', 'open'),
            default => $query,
        };

        if (filled($filters['type'] ?? null)) {
            $query->where('type', $filters['type']);
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['priority'] ?? null)) {
            $query->where('priority', $filters['priority']);
        }

        if ((int) ($filters['assigned_to'] ?? 0) > 0) {
            $query->where('assigned_to', (int) $filters['assigned_to']);
        }

        if ((int) ($filters['customer_id'] ?? 0) > 0) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }

        if ((int) ($filters['contact_id'] ?? 0) > 0) {
            $query->where('contact_id', (int) $filters['contact_id']);
        }

        if ((int) ($filters['opportunity_id'] ?? 0) > 0) {
            $query->where('opportunity_id', (int) $filters['opportunity_id']);
        }

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('subject', 'like', '%'.$search.'%')
                    ->orWhere('body', 'like', '%'.$search.'%');
            });
        }

        return $query;
    }

    protected function defaultDirection(string $type): ?string
    {
        return in_array($type, ['call', 'email'], true) ? 'outbound' : null;
    }
}
