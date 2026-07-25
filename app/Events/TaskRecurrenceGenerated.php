<?php

namespace App\Events;

final class TaskRecurrenceGenerated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.recurrence.generated';
    }
}
