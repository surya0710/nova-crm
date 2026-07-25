<?php

namespace App\Events;

final class ResourceAllocationUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'resource.allocation_updated';
    }
}
