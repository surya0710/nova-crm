<?php

namespace App\Events;

final class ProjectDependencyUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.dependency.updated';
    }
}
