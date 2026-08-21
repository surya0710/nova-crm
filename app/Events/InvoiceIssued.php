<?php

namespace App\Events;

final class InvoiceIssued extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'invoice.issued';
    }
}
