<?php

namespace App\Events;

final class EmployeeExited extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'employee.exited';
    }
}
