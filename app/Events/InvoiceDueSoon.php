<?php

namespace App\Events;

final class InvoiceDueSoon extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'invoice.due_soon';
    }
}
