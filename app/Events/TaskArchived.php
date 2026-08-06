<?php

namespace App\Events;

final class TaskArchived extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.archived';
    }
}
