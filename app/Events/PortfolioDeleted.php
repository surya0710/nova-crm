<?php

namespace App\Events;

final class PortfolioDeleted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'portfolio.deleted';
    }
}
