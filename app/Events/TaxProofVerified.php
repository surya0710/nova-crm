<?php

namespace App\Events;

final class TaxProofVerified extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'tax.proof.verified';
    }
}
