<?php

namespace App\Events;

final class CustomerFirstInvoice extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'customer.first_invoice';
    }
}
