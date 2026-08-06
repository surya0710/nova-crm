<?php

namespace App\Events;

final class PortfolioHealthUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'portfolio.health.updated';
    }
}
