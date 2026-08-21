<?php

namespace App\Events;

final class QuotationAccepted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'quotation.accepted';
    }
}
