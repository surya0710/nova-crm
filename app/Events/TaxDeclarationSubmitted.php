<?php

namespace App\Events;

final class TaxDeclarationSubmitted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'tax.declaration.submitted';
    }
}
