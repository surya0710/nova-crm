<?php

namespace App\Events;

final class PayrollLedgerGenerated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'payroll.ledger.generated';
    }
}
