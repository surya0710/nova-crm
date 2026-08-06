<?php

namespace App\Events;

final class PayrollValidationFailed extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'payroll.validation.failed';
    }
}
