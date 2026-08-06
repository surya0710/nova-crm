<?php

namespace App\Events;

final class OfferExpired extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.offer_expired';
    }
}
