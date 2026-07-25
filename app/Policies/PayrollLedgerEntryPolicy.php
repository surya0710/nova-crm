<?php

namespace App\Policies;

use App\Models\PayrollLedgerEntry;
use App\Models\User;

class PayrollLedgerEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.finance.view');
    }

    public function view(User $user, PayrollLedgerEntry $entry): bool
    {
        return $user->hasPermission('payroll.finance.view', $entry->organization);
    }

    public function generate(User $user): bool
    {
        return $user->hasPermission('payroll.finance.manage');
    }
}
