<?php

namespace App\Events;

final class LeaveApproved extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'leave.approved';
    }
}
