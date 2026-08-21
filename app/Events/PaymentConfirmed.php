<?php

namespace App\Events;

final class PaymentConfirmed extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'payment.confirmed';
    }
}
