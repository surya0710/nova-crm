<?php

namespace App\Events;

final class ProjectMemberRemoved extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.member_removed';
    }
}
