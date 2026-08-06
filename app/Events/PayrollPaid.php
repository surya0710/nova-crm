<?php

namespace App\Events;

final class PayrollPaid extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'payroll.paid';
    }
}
