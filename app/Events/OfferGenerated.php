<?php

namespace App\Events;

final class OfferGenerated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.offer_generated';
    }
}
