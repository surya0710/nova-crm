<?php

namespace App\Events;

final class PaymentReceived extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'payment.received';
    }
}
