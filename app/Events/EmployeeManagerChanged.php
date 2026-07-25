<?php

namespace App\Events;

final class EmployeeManagerChanged extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'employee.manager_changed';
    }
}
