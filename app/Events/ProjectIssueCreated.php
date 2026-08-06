<?php

namespace App\Events;

final class ProjectIssueCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.issue.created';
    }
}
