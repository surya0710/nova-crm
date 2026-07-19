<?php

namespace App\Events;

final class OpportunityStageChanged extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'opportunity.stage_changed';
    }
}
