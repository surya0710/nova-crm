<?php

namespace App\Events;

final class TaskLabelAttached extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.label.attached';
    }
}
