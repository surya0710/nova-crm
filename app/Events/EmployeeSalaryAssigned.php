<?php

namespace App\Events;

final class EmployeeSalaryAssigned extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'employee.salary_assigned';
    }
}
