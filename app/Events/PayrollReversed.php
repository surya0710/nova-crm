<?php

namespace App\Events;

final class PayrollReversed extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'payroll.reversed';
    }
}
