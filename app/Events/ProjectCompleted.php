<?php

namespace App\Events;

final class ProjectCompleted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.completed';
    }
}
