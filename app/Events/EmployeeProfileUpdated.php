<?php

namespace App\Events;

final class EmployeeProfileUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'employee.profile_updated';
    }
}
