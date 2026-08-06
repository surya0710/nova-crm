<?php

namespace App\Services\Assignment\Strategies;

use App\Models\AssignmentPool;
use App\Services\Assignment\AssignmentContext;
use App\Services\Assignment\AssignmentResult;
use App\Services\Assignment\Contracts\AssignmentStrategyInterface;

/**
 * Leaves the record unassigned for manual pickup.
 */
class ManualQueueStrategy implements AssignmentStrategyInterface
{
    public function key(): string
    {
        return 'manual_queue';
    }

    public function assign(AssignmentPool $pool, AssignmentContext $context): AssignmentResult
    {
        return AssignmentResult::unassigned(
            message: 'Manual queue — left unassigned.',
            pool: $pool,
            strategy: $this->key(),
        );
    }
}
