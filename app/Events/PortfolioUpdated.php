<?php

namespace App\Events;

final class PortfolioUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'portfolio.updated';
    }
}
