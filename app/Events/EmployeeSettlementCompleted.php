<?php

namespace App\Events;

final class EmployeeSettlementCompleted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'employee.settlement.completed';
    }
}
