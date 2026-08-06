<?php

namespace App\Events;

final class PayrollAdjustmentApproved extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'payroll.adjustment.approved';
    }
}
