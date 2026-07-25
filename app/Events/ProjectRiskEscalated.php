<?php

namespace App\Events;

final class ProjectRiskEscalated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.risk.escalated';
    }
}
