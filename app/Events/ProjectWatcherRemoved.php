<?php

namespace App\Events;

final class ProjectWatcherRemoved extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.watcher.removed';
    }
}
