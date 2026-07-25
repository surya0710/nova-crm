<?php

namespace App\Events;

final class GoalAssigned extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'goal.assigned';
    }
}
