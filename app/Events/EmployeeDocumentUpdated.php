<?php

namespace App\Events;

final class EmployeeDocumentUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'employee_document.updated';
    }
}
