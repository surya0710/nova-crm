<?php

namespace App\Events;

final class TaskCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.created';
    }
}
