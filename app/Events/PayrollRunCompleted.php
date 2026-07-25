<?php

namespace App\Events;

final class PayrollRunCompleted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'payroll.run.completed';
    }
}
