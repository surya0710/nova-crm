<?php

namespace App\Events;

final class EmployeeDepartmentChanged extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'employee.department_changed';
    }
}
