<?php

namespace App\Events;

final class ResourceAllocated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'resource.allocated';
    }
}
