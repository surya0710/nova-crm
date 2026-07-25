<?php

namespace App\Events;

final class ProjectIssueResolved extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.issue.resolved';
    }
}
