<?php

namespace App\Events;

final class TaskWatcherAdded extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.watcher.added';
    }
}
