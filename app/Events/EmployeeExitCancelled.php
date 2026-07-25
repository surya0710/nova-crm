<?php

namespace App\Events;

final class EmployeeExitCancelled extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'employee.exit.cancelled';
    }
}
