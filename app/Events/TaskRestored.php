<?php

namespace App\Events;

final class TaskRestored extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.restored';
    }
}
