<?php

namespace App\Events;

final class TaskRecurrenceUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.recurrence.updated';
    }
}
