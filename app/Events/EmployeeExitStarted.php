<?php

namespace App\Events;

final class EmployeeExitStarted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'employee.exit.started';
    }
}
