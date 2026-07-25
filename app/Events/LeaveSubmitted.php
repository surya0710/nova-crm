<?php

namespace App\Events;

final class LeaveSubmitted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'leave.submitted';
    }
}
