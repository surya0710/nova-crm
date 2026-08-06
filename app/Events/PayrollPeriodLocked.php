<?php

namespace App\Events;

final class PayrollPeriodLocked extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'payroll.period.locked';
    }
}
