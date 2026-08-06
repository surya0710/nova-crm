<?php

namespace App\Events;

final class CustomerUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'customer.updated';
    }
}
