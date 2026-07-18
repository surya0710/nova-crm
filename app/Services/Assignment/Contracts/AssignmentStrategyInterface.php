<?php

namespace App\Services\Assignment\Contracts;

use App\Models\AssignmentPool;
use App\Services\Assignment\AssignmentContext;
use App\Services\Assignment\AssignmentResult;

interface AssignmentStrategyInterface
{
    public function key(): string;

    /**
     * Select an assignee from the pool. Must be concurrency-safe where state is mutated.
     */
    public function assign(AssignmentPool $pool, AssignmentContext $context): AssignmentResult;
}
