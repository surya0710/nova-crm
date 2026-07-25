<?php

namespace App\Events;

final class ProjectWatcherAdded extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.watcher.added';
    }
}
