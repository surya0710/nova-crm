<?php

namespace App\Events;

final class ProjectCollaborationUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.collaboration.updated';
    }
}
