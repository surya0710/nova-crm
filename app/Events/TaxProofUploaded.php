<?php

namespace App\Events;

final class TaxProofUploaded extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'tax.proof.uploaded';
    }
}
