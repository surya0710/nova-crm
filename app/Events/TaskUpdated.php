<?php

namespace App\Events;

final class TaskUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.updated';
    }
}
