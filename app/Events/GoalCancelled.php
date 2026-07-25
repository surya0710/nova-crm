<?php

namespace App\Events;

final class GoalCancelled extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'goal.cancelled';
    }
}
