<?php

namespace App\Events;

final class OfferSent extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.offer_sent';
    }
}
