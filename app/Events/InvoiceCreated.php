<?php

namespace App\Events;

final class InvoiceCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'invoice.created';
    }
}
