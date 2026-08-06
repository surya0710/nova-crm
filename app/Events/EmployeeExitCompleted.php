<?php

namespace App\Events;

final class EmployeeExitCompleted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'employee.exit.completed';
    }
}
