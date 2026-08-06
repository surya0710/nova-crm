<?php

namespace App\Events;

final class EmployeeDocumentVerified extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'employee_document.verified';
    }
}
