<?php

namespace App\Events;

final class ProjectLabelUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.label.updated';
    }
}
