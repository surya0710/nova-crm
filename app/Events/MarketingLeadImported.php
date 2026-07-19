<?php

namespace App\Events;

final class MarketingLeadImported extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'marketing.lead_imported';
    }
}
