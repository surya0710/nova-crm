<?php

namespace App\Events;

final class OpportunityLost extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'opportunity.lost';
    }
}
