<?php

namespace App\Events;

final class LeadCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'lead.created';
    }
}
