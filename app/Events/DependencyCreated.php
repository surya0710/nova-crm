<?php

namespace App\Events;

final class DependencyCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.dependency_created';
    }
}
