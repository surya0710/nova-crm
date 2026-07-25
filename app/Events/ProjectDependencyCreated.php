<?php

namespace App\Events;

final class ProjectDependencyCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.dependency.created';
    }
}
