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

class RoundRobinStrategy implements AssignmentStrategyInterface
{
    public function key(): string
    {
        return 'round_robin';
    }

    public function assign(AssignmentPool $pool, AssignmentContext $context): AssignmentResult
    {
        return DB::transaction(function () use ($pool) {
            /** @var AssignmentPool $locked */
            $locked = AssignmentPool::query()
                ->whereKey($pool->id)
                ->lockForUpdate()
                ->firstOrFail();

            $members = $this->activeMembers($locked);

            if ($members->isEmpty()) {
                return AssignmentResult::unassigned(
                    message: 'No active pool members.',
                    pool: $locked,
                    strategy: $this->key(),
                );
            }

            $index = ((int) $locked->rotation_position) % $members->count();
            /** @var AssignmentPoolMember $member */
            $member = $members->values()->get($index);

            $locked->rotation_position = ((int) $locked->rotation_position) + 1;
            $locked->save();

            $user = User::query()->find($member->user_id);

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
     * @return Collection<int, AssignmentPoolMember>
     */
    protected function activeMembers(AssignmentPool $pool): Collection
    {
        return AssignmentPoolMember::query()
            ->where('assignment_pool_id', $pool->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }
}
