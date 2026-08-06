<?php

namespace App\Events;

final class PayrollBankExported extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'payroll.bank.exported';
    }
}
