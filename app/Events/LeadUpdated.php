<?php

namespace App\Events;

final class LeadUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'lead.updated';
    }
}
