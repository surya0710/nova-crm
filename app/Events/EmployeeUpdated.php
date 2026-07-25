<?php

namespace App\Events;

final class EmployeeUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'employee.updated';
    }
}
