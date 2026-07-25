<?php

namespace App\Events;

final class DependencyRemoved extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.dependency_removed';
    }
}
