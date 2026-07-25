<?php

namespace App\Events;

final class PortfolioReportGenerated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'portfolio.report.generated';
    }
}
