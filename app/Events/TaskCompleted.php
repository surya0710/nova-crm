<?php

namespace App\Events;

final class TaskCompleted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.completed';
    }
}
