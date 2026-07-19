<?php

namespace App\Services;

use App\Events\LeadAssigned;
use App\Events\LeadCreated;
use App\Events\LeadUpdated;
use App\Exceptions\DuplicateLeadException;
use App\Models\AssignmentHistory;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Services\Assignment\AssignmentContext;
use App\Services\Assignment\AssignmentResult;
use App\Services\Assignment\AssignmentService;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected LeadNormalizationService $normalizer,
        protected MetadataEntityFormService $metadataForms,
        protected MarketingAttributionService $attribution,
        protected MarketingConversionService $conversions,
        protected AssignmentService $assignment,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(
        array $data,
        User $user,
        string $assignmentReason = AssignmentHistory::REASON_AUTOMATIC,
        array $metadataValues = [],
    ): Lead {
        $signals = $this->extractAttributionSignals($data);
        unset($data['visitor_uuid'], $data['session_uuid']);

        $assignmentResult = null;
        $usedAutoAssignment = false;

        if (! $this->assignment->hasExplicitOwner($data)) {
            $organizationId = (int) ($data['organization_id'] ?? app(TenantContext::class)->id());
            $context = AssignmentContext::forLead($organizationId, $data);
            $assignmentResult = $this->assignment->resolve($context);
            $data['assigned_to'] = $assignmentResult->assigneeId();
            $usedAutoAssignment = true;
        }

        $lead = Lead::query()->create([
            ...$data,
            'created_by' => $user->id,
        ]);

        if ($usedAutoAssignment && $assignmentResult instanceof AssignmentResult && $assignmentResult->matched) {
            $this->assignment->recordHistory(
                context: AssignmentContext::forLead((int) $lead->organization_id, $data),
                entityId: (int) $lead->id,
                result: $assignmentResult,
                reason: $assignmentReason,
                assignedBy: $user,
                previousOwnerId: null,
            );

            if ($assignmentResult->assigneeId()) {
                $this->auditLogger->log($lead, 'assigned', [
                    'from' => null,
                    'to' => $assignmentResult->assigneeId(),
                    'via' => 'assignment_platform',
                    'strategy' => $assignmentResult->strategy,
                    'rule_id' => $assignmentResult->rule?->id,
                    'reason' => $assignmentReason,
                ], $user);
            }
        }

        $this->attribution->attributeLead($lead, $signals);
        $this->conversions->recordLeadCreated($lead->fresh());

        $this->metadataForms->persistValidatedValues($lead, $metadataValues);
        $lead = $lead->fresh();
        if ($lead->assigned_to) {
            event(LeadAssigned::forModel($lead, [
                'previous_owner_id' => null,
                'owner_id' => (int) $lead->assigned_to,
                'actor_id' => $user->id,
            ]));
        }
        event(LeadCreated::forModel($lead, ['actor_id' => $user->id]));

        return $lead;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createFromApi(array $payload, User $user, Organization $organization): Lead
    {
        $signals = $this->extractAttributionSignals($payload);
        $data = $this->normalizer->normalize($payload);
        unset($data['visitor_uuid'], $data['session_uuid']);

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

        return DB::transaction(function () use ($data, $user, $organization, $message, $payload, $metadataValues, $signals) {
            $leadPayload = [
                'organization_id' => $organization->id,
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'source' => $data['source'] ?? 'api',
                'status' => 'new',
                'priority' => $data['priority'] ?? 'medium',
                'assigned_to' => $data['assigned_to'] ?? null,
                'custom_fields' => $data['custom_fields'] ?? [],
            ];

            if (isset($payload['country'])) {
                $leadPayload['country'] = $payload['country'];
            }

            $assignmentResult = null;
            $usedAutoAssignment = false;

            if (! $this->assignment->hasExplicitOwner($leadPayload)) {
                $context = AssignmentContext::forLead($organization->id, $leadPayload);
                $assignmentResult = $this->assignment->resolve($context);
                $leadPayload['assigned_to'] = $assignmentResult->assigneeId();
                $usedAutoAssignment = true;
            }

            unset($leadPayload['country'], $leadPayload['custom_fields']);

            $lead = Lead::query()->create([
                ...$leadPayload,
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

            if ($usedAutoAssignment && $assignmentResult instanceof AssignmentResult && $assignmentResult->matched) {
                $this->assignment->recordHistory(
                    context: AssignmentContext::forLead($organization->id, [
                        'source' => $lead->source,
                        'status' => $lead->status,
                        'custom_fields' => $data['custom_fields'] ?? [],
                    ]),
                    entityId: (int) $lead->id,
                    result: $assignmentResult,
                    reason: AssignmentHistory::REASON_API,
                    assignedBy: $user,
                    previousOwnerId: null,
                );

                if ($assignmentResult->assigneeId()) {
                    $this->auditLogger->log($lead, 'assigned', [
                        'from' => null,
                        'to' => $assignmentResult->assigneeId(),
                        'via' => 'assignment_platform',
                        'strategy' => $assignmentResult->strategy,
                        'rule_id' => $assignmentResult->rule?->id,
                        'reason' => AssignmentHistory::REASON_API,
                    ], $user);
                }
            }

            $this->attribution->attributeLead($lead, $signals);

            $this->conversions->recordLeadCreated($lead);

            $this->auditLogger->log($lead, 'received_via_api', [
                'source' => $lead->source,
                'form_type' => $payload['form_type'] ?? null,
                'source_url' => $payload['source_url'] ?? null,
            ], $user);

            $this->notifyApiLeadRecipients($lead, $user);

            $lead = $lead->fresh();
            if ($lead->assigned_to) {
                event(LeadAssigned::forModel($lead, [
                    'previous_owner_id' => null,
                    'owner_id' => (int) $lead->assigned_to,
                    'actor_id' => $user->id,
                ]));
            }
            event(LeadCreated::forModel($lead, ['actor_id' => $user->id, 'source' => 'api']));

            return $lead;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{visitor_uuid?: string|null, session_uuid?: string|null}
     */
    protected function extractAttributionSignals(array $payload): array
    {
        $signals = [];

        if (array_key_exists('visitor_uuid', $payload)) {
            $signals['visitor_uuid'] = $payload['visitor_uuid'];
        }

        if (array_key_exists('session_uuid', $payload)) {
            $signals['session_uuid'] = $payload['session_uuid'];
        }

        return $signals;
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

    public function update(Lead $lead, array $data, User $actor, array $metadataValues = []): Lead
    {
        $beforeOwner = $lead->assigned_to;
        $ordinaryChanges = collect($data)->except(['assigned_to'])->all();
        $before = $lead->only(array_keys($ordinaryChanges));

        $lead->update($ordinaryChanges);
        if (array_key_exists('assigned_to', $data) && (int) $beforeOwner !== (int) $data['assigned_to']) {
            $this->assignment->assignOwner($lead, $data['assigned_to'] ? (int) $data['assigned_to'] : null, $actor);
        }

        $metadataResult = $this->metadataForms->persistValidatedValues($lead, $metadataValues);
        $lead = $lead->fresh();
        $changed = array_keys(array_filter(
            $ordinaryChanges,
            fn (mixed $value, string $key) => $before[$key] != $lead->getAttribute($key),
            ARRAY_FILTER_USE_BOTH,
        ));
        if ($metadataResult['changed'] ?? false) {
            $changed[] = 'custom_fields';
        }
        if ($changed !== []) {
            $runtime = app(WorkflowRuntimeContext::class);
            event(LeadUpdated::forModel(
                $lead,
                ['actor_id' => $actor->id, 'changes' => $changed],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));
        }

        return $lead;
    }

    public function changeStatus(Lead $lead, string $status, User $actor): Lead
    {
        $allowed = array_keys(config('leads.statuses', []));
        if ($allowed !== [] && ! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => 'Invalid lead status.']);
        }

        return $this->update($lead, ['status' => $status], $actor);
    }
}
