<?php

namespace App\Events;

final class PayslipEmailed extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'payslip.emailed';
    }
}
