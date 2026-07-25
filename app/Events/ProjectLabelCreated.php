<?php

namespace App\Events;

final class ProjectLabelCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.label.created';
    }
}
