<?php

namespace App\Events;

final class TaxDeclarationApproved extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'tax.declaration.approved';
    }
}
