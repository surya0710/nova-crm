<?php

namespace App\Events;

final class ProjectMilestoneCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.milestone_created';
    }
}
