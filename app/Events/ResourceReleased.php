<?php

namespace App\Events;

final class ResourceReleased extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'resource.released';
    }
}
