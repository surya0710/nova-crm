<?php

namespace App\Events;

final class ChecklistCompleted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.checklist_completed';
    }
}
