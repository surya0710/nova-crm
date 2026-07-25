<?php

namespace App\Events;

final class PayrollStatutoryCalculated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'payroll.statutory.calculated';
    }
}
