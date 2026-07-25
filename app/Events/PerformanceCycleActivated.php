<?php

namespace App\Events;

final class PerformanceCycleActivated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'performance.cycle.activated';
    }
}
