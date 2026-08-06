<?php

namespace App\Events;

final class PayrollRunStarted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'payroll.run.started';
    }
}
