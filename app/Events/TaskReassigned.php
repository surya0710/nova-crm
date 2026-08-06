<?php

namespace App\Events;

final class TaskReassigned extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.reassigned';
    }
}
