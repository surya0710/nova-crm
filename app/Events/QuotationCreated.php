<?php

namespace App\Events;

final class QuotationCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'quotation.created';
    }
}
