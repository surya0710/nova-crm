<?php

namespace App\Events;

final class GoalProgressUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'goal.progress.updated';
    }
}
