<?php

namespace App\Policies;

use App\Models\PayrollResult;
use App\Models\User;

class PayrollResultPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.view');
    }

    public function view(User $user, PayrollResult $payrollResult): bool
    {
        return $user->hasPermission('payroll.view', $payrollResult->organization);
    }
}
