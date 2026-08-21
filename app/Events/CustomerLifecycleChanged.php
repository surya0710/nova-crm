<?php

namespace App\Events;

final class CustomerLifecycleChanged extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'customer.lifecycle_changed';
    }
}
