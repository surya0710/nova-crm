<?php

namespace App\Events;

final class LeaveRejected extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'leave.rejected';
    }
}
