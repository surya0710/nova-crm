<?php

namespace App\Events;

final class ProjectRiskUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.risk.updated';
    }
}
