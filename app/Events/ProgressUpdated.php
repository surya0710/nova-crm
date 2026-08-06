<?php

namespace App\Events;

final class ProgressUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.progress.updated';
    }
}
