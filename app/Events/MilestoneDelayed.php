<?php

namespace App\Events;

final class MilestoneDelayed extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.milestone.delayed';
    }
}
