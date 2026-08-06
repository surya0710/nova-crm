<?php

namespace App\Events;

final class ProjectCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.created';
    }
}
