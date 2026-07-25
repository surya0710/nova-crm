<?php

namespace App\Events;

final class ProjectRiskCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.risk.created';
    }
}
