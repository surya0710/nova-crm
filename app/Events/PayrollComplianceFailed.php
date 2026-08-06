<?php

namespace App\Events;

final class PayrollComplianceFailed extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'payroll.compliance.failed';
    }
}
