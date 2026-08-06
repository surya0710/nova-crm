<?php

namespace App\Events;

final class ProjectMemberAssigned extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.member_assigned';
    }
}
