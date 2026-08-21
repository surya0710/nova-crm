<?php

namespace App\Events;

final class TicketStatusChanged extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'ticket.status_changed';
    }
}
