<?php

namespace App\Events;

final class ProjectUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.updated';
    }
}
