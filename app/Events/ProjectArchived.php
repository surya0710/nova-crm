<?php

namespace App\Events;

final class ProjectArchived extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.archived';
    }
}
