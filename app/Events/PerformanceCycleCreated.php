<?php

namespace App\Events;

final class PerformanceCycleCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'performance.cycle.created';
    }
}
