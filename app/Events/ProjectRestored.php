<?php

namespace App\Events;

final class ProjectRestored extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.restored';
    }
}
