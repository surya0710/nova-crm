<?php

namespace App\Events;

final class ProjectLifecycleChanged extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.lifecycle_changed';
    }
}
