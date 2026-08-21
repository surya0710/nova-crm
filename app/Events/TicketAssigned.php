<?php

namespace App\Events;

final class TicketAssigned extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'ticket.assigned';
    }
}
