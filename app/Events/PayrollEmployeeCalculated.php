<?php

namespace App\Events;

final class PayrollEmployeeCalculated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'payroll.employee.calculated';
    }
}
