<?php

namespace App\Events;

final class LeadAssigned extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'lead.assigned';
    }
}
