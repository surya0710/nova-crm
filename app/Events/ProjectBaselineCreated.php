<?php

namespace App\Events;

final class ProjectBaselineCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.baseline.created';
    }
}
