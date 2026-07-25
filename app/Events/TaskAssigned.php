<?php

namespace App\Events;

final class TaskAssigned extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.assigned';
    }
}
