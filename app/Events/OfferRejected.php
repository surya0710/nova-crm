<?php

namespace App\Events;

final class OfferRejected extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.offer_rejected';
    }
}
