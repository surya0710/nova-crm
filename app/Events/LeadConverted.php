<?php

namespace App\Events;

final class LeadConverted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'lead.converted';
    }
}
