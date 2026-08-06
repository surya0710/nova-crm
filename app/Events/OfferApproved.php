<?php

namespace App\Events;

final class OfferApproved extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.offer_approved';
    }
}
