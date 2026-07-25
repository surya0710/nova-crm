<?php

namespace App\Events;

final class TaskRecurrenceCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.recurrence.created';
    }
}
