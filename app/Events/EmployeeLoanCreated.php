<?php

namespace App\Events;

final class EmployeeLoanCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'employee.loan.created';
    }
}
