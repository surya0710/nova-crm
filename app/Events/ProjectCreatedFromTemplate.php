<?php

namespace App\Events;

final class ProjectCreatedFromTemplate extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.created_from_template';
    }
}
