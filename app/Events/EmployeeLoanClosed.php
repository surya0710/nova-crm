<?php

namespace App\Events;

final class EmployeeLoanClosed extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'employee.loan.closed';
    }
}
