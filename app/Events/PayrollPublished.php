<?php

namespace App\Events;

final class PayrollPublished extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'payroll.published';
    }
}
