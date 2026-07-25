<?php

namespace App\Events;

final class LeaveCancelled extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'leave.cancelled';
    }
}
