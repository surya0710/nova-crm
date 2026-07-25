<?php

namespace App\Events;

final class EmployeeCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'employee.created';
    }
}
