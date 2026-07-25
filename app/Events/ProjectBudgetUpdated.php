<?php

namespace App\Events;

final class ProjectBudgetUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.budget.updated';
    }
}
