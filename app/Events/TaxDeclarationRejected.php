<?php

namespace App\Events;

final class TaxDeclarationRejected extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'tax.declaration.rejected';
    }
}
