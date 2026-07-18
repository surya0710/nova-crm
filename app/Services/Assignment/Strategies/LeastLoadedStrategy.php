<?php

namespace App\Services\Assignment\Strategies;

use App\Models\AssignmentPool;
use App\Models\AssignmentPoolMember;
use App\Models\Lead;
use App\Models\User;
use App\Services\Assignment\AssignmentContext;
use App\Services\Assignment\AssignmentResult;
use App\Services\Assignment\Contracts\AssignmentStrategyInterface;
use Illuminate\Support\Facades\DB;

/**
 * Assign to the active pool member with the lowest open-entity workload.
 *
 * v1: for leads, open = status not in converted/won/lost.
 * Ties break by lowest member id (deterministic).
 */
class LeastLoadedStrategy implements AssignmentStrategyInterface
{
    public function key(): string
    {
        return 'least_loaded';
    }

    public function assign(AssignmentPool $pool, AssignmentContext $context): AssignmentResult
    {
        $members = AssignmentPoolMember::query()
            ->where('assignment_pool_id', $pool->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($members->isEmpty()) {
            return AssignmentResult::unassigned(
                message: 'No active pool members.',
                pool: $pool,
                strategy: $this->key(),
            );
        }

        $workloads = $this->workloadsFor($context->entityType, $pool->organization_id, $members->pluck('user_id')->all());

        $bestMember = null;
        $bestLoad = PHP_INT_MAX;

        foreach ($members as $member) {
            $load = $workloads[(int) $member->user_id] ?? 0;
            if ($load < $bestLoad) {
                $bestLoad = $load;
                $bestMember = $member;
            }
        }

        if (! $bestMember) {
            return AssignmentResult::unassigned(
                message: 'Unable to resolve least loaded member.',
                pool: $pool,
                strategy: $this->key(),
            );
        }

        $user = User::query()->find($bestMember->user_id);

        if (! $user) {
            return AssignmentResult::unassigned(
                message: 'Selected member user missing.',
                pool: $pool,
                strategy: $this->key(),
            );
        }

        return new AssignmentResult(
            assignee: $user,
            strategy: $this->key(),
            pool: $pool,
            matched: true,
        );
    }

    /**
     * @param  list<int>  $userIds
     * @return array<int, int>
     */
    protected function workloadsFor(string $entityType, int $organizationId, array $userIds): array
    {
        $config = config("assignment.least_loaded.{$entityType}");

        if (! is_array($config) || $entityType !== 'lead') {
            return array_fill_keys($userIds, 0);
        }

        $excluded = $config['open_statuses_excluded'] ?? ['converted', 'won', 'lost'];
        $ownerColumn = $config['owner_column'] ?? 'assigned_to';

        $counts = Lead::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->whereIn($ownerColumn, $userIds)
            ->whereNotIn('status', $excluded)
            ->select($ownerColumn, DB::raw('COUNT(*) as workload'))
            ->groupBy($ownerColumn)
            ->pluck('workload', $ownerColumn)
            ->all();

        $result = [];
        foreach ($userIds as $userId) {
            $result[$userId] = (int) ($counts[$userId] ?? 0);
        }

        return $result;
    }
}
