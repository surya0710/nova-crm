<?php

namespace App\Policies;

use App\Models\PayrollReversal;
use App\Models\User;

class PayrollReversalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.finance.view');
    }

    public function view(User $user, PayrollReversal $reversal): bool
    {
        return $user->hasPermission('payroll.finance.view', $reversal->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payroll.finance.manage');
    }
}
