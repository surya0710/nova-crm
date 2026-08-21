<?php

namespace App\Events;

final class QuotationExpiring extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'quotation.expiring';
    }
}
