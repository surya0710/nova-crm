<?php

namespace App\Events;

final class EmployeeDocumentDeleted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'employee_document.deleted';
    }
}
