<?php

namespace App\Events;

final class OpportunityCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'opportunity.created';
    }
}
