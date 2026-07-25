<?php

namespace App\Events;

final class GoalCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'goal.created';
    }
}
