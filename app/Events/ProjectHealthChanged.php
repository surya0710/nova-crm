<?php

namespace App\Events;

final class ProjectHealthChanged extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.health.changed';
    }
}
