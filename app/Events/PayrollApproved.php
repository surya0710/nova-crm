<?php

namespace App\Events;

final class PayrollApproved extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'payroll.approved';
    }
}
