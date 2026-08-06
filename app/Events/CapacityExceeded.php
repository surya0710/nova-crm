<?php

namespace App\Events;

final class CapacityExceeded extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'resource.capacity_exceeded';
    }
}
