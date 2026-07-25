<?php

namespace App\Events;

final class PayrollPeriodCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'payroll.period.created';
    }
}
