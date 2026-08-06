<?php

namespace App\Events;

final class EmployeeDocumentUploaded extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'employee_document.uploaded';
    }
}
