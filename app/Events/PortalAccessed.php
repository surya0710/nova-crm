<?php

namespace App\Events;

final class PortalAccessed extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'portal.accessed';
    }
}
