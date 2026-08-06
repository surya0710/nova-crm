<?php

namespace App\Events;

final class TaskStarted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.started';
    }
}
