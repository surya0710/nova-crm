<?php

namespace App\Events;

final class CustomerCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'customer.created';
    }
}
