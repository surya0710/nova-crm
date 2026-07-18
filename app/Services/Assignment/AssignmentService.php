<?php

namespace App\Services\Assignment;

use App\Models\AssignmentHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Sole orchestration entry for assignment resolution and history.
 *
 * Consumers ask: "Who should own this record?" — nothing else.
 */
class AssignmentService
{
    public function __construct(
        protected AssignmentRuleEngine $engine,
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
}
