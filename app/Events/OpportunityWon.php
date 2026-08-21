<?php

namespace App\Events;

final class OpportunityWon extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'opportunity.won';
    }
}
