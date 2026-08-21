<?php

namespace App\Events;

final class InvoiceOverdue extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'invoice.overdue';
    }
}
