<?php

namespace App\Events;

final class ProjectMilestoneCompleted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.milestone_completed';
    }
}
