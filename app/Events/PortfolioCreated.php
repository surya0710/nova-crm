<?php

namespace App\Events;

final class PortfolioCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'portfolio.created';
    }
}
