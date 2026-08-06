<?php

namespace App\Events;

final class TaskLabelDetached extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.label.detached';
    }
}
