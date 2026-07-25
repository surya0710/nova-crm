<?php

namespace App\Events;

final class TaskWatcherRemoved extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.watcher.removed';
    }
}
