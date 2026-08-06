<?php

namespace App\Events;

final class ReportGenerated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.report.generated';
    }
}
