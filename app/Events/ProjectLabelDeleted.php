<?php

namespace App\Events;

final class ProjectLabelDeleted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.label.deleted';
    }
}
