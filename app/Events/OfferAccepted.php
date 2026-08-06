<?php

namespace App\Events;

final class OfferAccepted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.offer_accepted';
    }
}
