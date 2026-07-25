<?php

namespace App\Events;

final class OverallocationDetected extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'resource.overallocation_detected';
    }
}
