<?php

namespace App\Events;

final class LeaveBalanceAdjusted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'leave.balance_adjusted';
    }
}
