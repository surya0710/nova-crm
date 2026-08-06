<?php

namespace App\Events;

final class EmployeeDocumentExpiring extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'employee_document.expiring';
    }
}
