<?php

namespace App\Events;

final class GoalCompleted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'goal.completed';
    }
}
