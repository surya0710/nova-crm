<?php

namespace App\Services;

use App\Exceptions\DuplicateLeadException;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Notifications\CrmNotification;
use Illuminate\Support\Facades\DB;

class LeadService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected LeadNormalizationService $normalizer,
        protected MetadataEntityFormService $metadataForms,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): Lead
    {
        return Lead::query()->create([
            ...$data,
            'created_by' => $user->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createFromApi(array $payload, User $user, Organization $organization): Lead
    {
        $data = $this->normalizer->normalize($payload);
        $metadataValues = $this->metadataForms->validatedValues(
            null,
            $organization,
            'lead',
            $data['custom_fields'] ?? [],
            allowUnknown: true,
            context: 'create',
        );

        $duplicate = $this->findDuplicate(
            $organization,
            $data['email'] ?? null,
            $data['phone'] ?? null,
        );

        if ($duplicate) {
            throw new DuplicateLeadException($duplicate);
        }

        $message = $data['message'] ?? null;
        unset($data['message']);

        return DB::transaction(function () use ($data, $user, $organization, $message, $payload, $metadataValues) {
            $lead = Lead::query()->create([
                'organization_id' => $organization->id,
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'source' => $data['source'] ?? 'api',
                'status' => 'new',
                'priority' => $data['priority'] ?? 'medium',
                'assigned_to' => $data['assigned_to'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->metadataForms->persistValidatedValues($lead, $metadataValues, allowUnknown: true);

            if ($message) {
                LeadNote::query()->create([
                    'organization_id' => $organization->id,
                    'lead_id' => $lead->id,
                    'user_id' => $user->id,
                    'body' => $message,
                ]);
            }

            $this->auditLogger->log($lead, 'received_via_api', [
                'source' => $lead->source,
                'form_type' => $payload['form_type'] ?? null,
                'source_url' => $payload['source_url'] ?? null,
            ], $user);

            $this->notifyApiLeadRecipients($lead, $user);

            return $lead->fresh();
        });
    }

    public function findDuplicate(Organization $organization, ?string $email, ?string $phone): ?Lead
    {
        $email = trim($email ?? '');
        $phone = $this->normalizer->normalizePhone($phone) ?? '';

        if ($email === '' && $phone === '') {
            return null;
        }

        return Lead::query()
            ->where('organization_id', $organization->id)
            ->whereNotIn('status', ['converted', 'won', 'lost'])
            ->where(function ($query) use ($email, $phone) {
                if ($email !== '') {
                    $query->orWhere('email', $email);
                }

                if ($phone !== '') {
                    $query->orWhere('phone', $phone);
                }
            })
            ->first();
    }

    protected function notifyApiLeadRecipients(Lead $lead, User $actor): void
    {
        $recipients = collect();

        if ($lead->assigned_to) {
            $assignee = User::query()->find($lead->assigned_to);

            if ($assignee) {
                $recipients->push($assignee);
            }
        } else {
            $organization = Organization::query()->find($lead->organization_id);

            if ($organization) {
                $users = $organization->users()->get();
                $roles = Role::query()
                    ->whereIn('id', $users->pluck('pivot.role_id')->filter()->unique())
                    ->with('permissions')
                    ->get()
                    ->keyBy('id');

                $recipients = $users->filter(function (User $user) use ($roles) {
                    $role = $roles->get($user->pivot->role_id);

                    return $role?->hasPermission('leads.manage') ?? false;
                });
            }
        }

        $recipients
            ->unique('id')
            ->reject(fn (User $user) => $user->id === $actor->id)
            ->each(function (User $recipient) use ($lead) {
                $recipient->notify(new CrmNotification(
                    title: __('New API lead'),
                    message: __('A new lead :name was submitted via API.', ['name' => $lead->name]),
                    actionUrl: route('leads.show', $lead),
                    organizationId: $lead->organization_id,
                ));
            });
    }
}
