<?php

namespace App\Services\Assignment;

use App\Events\LeadAssigned;
use App\Models\AssignmentHistory;
use App\Models\Lead;
use App\Models\User;
use App\Services\AuditLogger;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sole orchestration entry for assignment resolution and history.
 *
 * Consumers ask: "Who should own this record?" — nothing else.
 */
class AssignmentService
{
    public function __construct(
        protected AssignmentRuleEngine $engine,
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * Resolve an owner without persisting history.
     * Round-robin strategies still advance pool state under lock.
     */
    public function resolve(AssignmentContext $context): AssignmentResult
    {
        return $this->engine->resolve($context);
    }

    /**
     * Resolve owner and record assignment history for an existing entity.
     */
    public function assign(
        AssignmentContext $context,
        int $entityId,
        string $reason = AssignmentHistory::REASON_AUTOMATIC,
        ?User $assignedBy = null,
        ?int $previousOwnerId = null,
    ): AssignmentResult {
        return DB::transaction(function () use ($context, $entityId, $reason, $assignedBy, $previousOwnerId) {
            $result = $this->engine->resolve($context);

            $this->recordHistory(
                context: $context,
                entityId: $entityId,
                result: $result,
                reason: $reason,
                assignedBy: $assignedBy,
                previousOwnerId: $previousOwnerId,
            );

            return $result;
        });
    }

    /**
     * Persist history for a previously resolved assignment (e.g. after create).
     */
    public function recordHistory(
        AssignmentContext $context,
        int $entityId,
        AssignmentResult $result,
        string $reason = AssignmentHistory::REASON_AUTOMATIC,
        ?User $assignedBy = null,
        ?int $previousOwnerId = null,
    ): AssignmentHistory {
        return AssignmentHistory::query()->create([
            'organization_id' => $context->organizationId,
            'entity_type' => $context->entityType,
            'entity_id' => $entityId,
            'previous_owner_id' => $previousOwnerId,
            'new_owner_id' => $result->assigneeId(),
            'strategy' => $result->strategy,
            'assignment_rule_id' => $result->rule?->id,
            'assignment_pool_id' => $result->pool?->id,
            'assigned_by' => $assignedBy?->id,
            'reason' => $reason,
            'assigned_at' => now(),
        ]);
    }

    /**
     * Whether the given payload already has an explicit owner that must bypass auto-assignment.
     *
     * @param  array<string, mixed>  $data
     */
    public function hasExplicitOwner(array $data): bool
    {
        if (! array_key_exists('assigned_to', $data)) {
            return false;
        }

        $owner = $data['assigned_to'];

        return $owner !== null && $owner !== '' && (int) $owner > 0;
    }

    public function assignOwner(Model $entity, ?int $userId, User $actor, bool $automatic = false): AssignmentResult
    {
        if (! in_array('assigned_to', $entity->getFillable(), true)) {
            throw ValidationException::withMessages(['subject' => 'This entity cannot be assigned.']);
        }

        $organization = $entity->organization;
        if (! $organization?->users()->whereKey($actor->id)->exists()) {
            throw ValidationException::withMessages(['actor' => 'The actor is not an organization member.']);
        }

        return DB::transaction(function () use ($entity, $userId, $actor, $automatic) {
            $locked = $entity::query()->whereKey($entity->getKey())->lockForUpdate()->firstOrFail();
            $previous = $locked->assigned_to ? (int) $locked->assigned_to : null;
            $context = new AssignmentContext(
                (int) $locked->organization_id,
                strtolower(class_basename($locked)),
                ['status' => $locked->getAttribute('status'), 'metadata' => $locked->getAttribute('custom_fields') ?? []],
            );

            if ($automatic) {
                $result = $this->resolve($context);
                if (! $result->matched || ! $result->assigneeId()) {
                    return $result;
                }
            } else {
                $assignee = $userId
                    ? $locked->organization->users()->whereKey($userId)->first()
                    : null;
                if ($userId && ! $assignee) {
                    throw ValidationException::withMessages(['user_id' => 'The owner is not an organization member.']);
                }
                $result = new AssignmentResult($assignee, 'manual', matched: true);
            }

            $locked->updateQuietly(['assigned_to' => $result->assigneeId()]);
            $reason = $automatic
                ? AssignmentHistory::REASON_AUTOMATIC
                : ($previous === null ? AssignmentHistory::REASON_MANUAL : AssignmentHistory::REASON_REASSIGNED);
            $this->recordHistory($context, (int) $locked->getKey(), $result, $reason, $actor, $previous);

            if ($previous !== $result->assigneeId()) {
                $this->auditLogger->log($locked, 'assigned', [
                    'from' => $previous,
                    'to' => $result->assigneeId(),
                    'via' => 'assignment_platform',
                    'strategy' => $result->strategy,
                    'rule_id' => $result->rule?->id,
                    'reason' => $reason,
                ], $actor);
            }

            if ($locked instanceof Lead && $previous !== $result->assigneeId()) {
                $runtime = app(WorkflowRuntimeContext::class);
                event(LeadAssigned::forModel(
                    $locked->fresh(),
                    ['previous_owner_id' => $previous, 'owner_id' => $result->assigneeId(), 'actor_id' => $actor->id],
                    causationId: $runtime->causationId,
                    depth: $runtime->causationId ? $runtime->depth + 1 : 0,
                ));
            }

            return $result;
        });
    }
}
