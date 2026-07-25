<?php

namespace App\Events;

final class MilestoneCompleted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.milestone.completed';
    }
}
