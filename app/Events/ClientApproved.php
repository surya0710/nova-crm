<?php

namespace App\Events;

final class ClientApproved extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'client.approved';
    }
}
