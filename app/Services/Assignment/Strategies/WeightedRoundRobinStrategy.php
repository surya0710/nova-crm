<?php

namespace App\Services\Assignment\Strategies;

use App\Models\AssignmentPool;
use App\Models\AssignmentPoolMember;
use App\Models\User;
use App\Services\Assignment\AssignmentContext;
use App\Services\Assignment\AssignmentResult;
use App\Services\Assignment\Contracts\AssignmentStrategyInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Deterministic weighted round-robin.
 *
 * Expands active members by weight into a fixed sequence (stable order by id),
 * then advances rotation_position under row lock so concurrent callers never
 * share the same slot.
 */
class WeightedRoundRobinStrategy implements AssignmentStrategyInterface
{
    public function key(): string
    {
        return 'weighted_round_robin';
    }

    public function assign(AssignmentPool $pool, AssignmentContext $context): AssignmentResult
    {
        return DB::transaction(function () use ($pool) {
            /** @var AssignmentPool $locked */
            $locked = AssignmentPool::query()
                ->whereKey($pool->id)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence = $this->buildSequence($locked);

            if ($sequence === []) {
                return AssignmentResult::unassigned(
                    message: 'No active weighted pool members.',
                    pool: $locked,
                    strategy: $this->key(),
                );
            }

            $index = ((int) $locked->rotation_position) % count($sequence);
            $userId = $sequence[$index];

            $locked->rotation_position = ((int) $locked->rotation_position) + 1;
            $locked->save();

            $user = User::query()->find($userId);

            if (! $user) {
                return AssignmentResult::unassigned(
                    message: 'Selected member user missing.',
                    pool: $locked,
                    strategy: $this->key(),
                );
            }

            return new AssignmentResult(
                assignee: $user,
                strategy: $this->key(),
                pool: $locked,
                matched: true,
            );
        });
    }

    /**
     * @return list<int> user ids expanded by weight
     */
    protected function buildSequence(AssignmentPool $pool): array
    {
        /** @var Collection<int, AssignmentPoolMember> $members */
        $members = AssignmentPoolMember::query()
            ->where('assignment_pool_id', $pool->id)
            ->where('is_active', true)
            ->where('weight', '>', 0)
            ->orderBy('id')
            ->get();

        $sequence = [];

        foreach ($members as $member) {
            $weight = max(1, (int) $member->weight);
            for ($i = 0; $i < $weight; $i++) {
                $sequence[] = (int) $member->user_id;
            }
        }

        return $sequence;
    }
}
