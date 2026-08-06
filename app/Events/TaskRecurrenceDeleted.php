<?php

namespace App\Events;

final class TaskRecurrenceDeleted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.recurrence.deleted';
    }
}
