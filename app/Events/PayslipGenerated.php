<?php

namespace App\Events;

final class PayslipGenerated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'payslip.generated';
    }
}
