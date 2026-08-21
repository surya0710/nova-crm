<?php

namespace App\Events;

final class QuotationSent extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'quotation.sent';
    }
}
