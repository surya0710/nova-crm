<?php

namespace App\Events;

final class ProjectDelayed extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.delayed';
    }
}
