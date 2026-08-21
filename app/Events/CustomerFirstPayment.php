<?php

namespace App\Events;

final class CustomerFirstPayment extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'customer.first_payment';
    }
}
