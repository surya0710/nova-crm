<?php

namespace App\Policies;

use App\Models\SalaryAdvance;
use App\Models\User;

class SalaryAdvancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.finance.view');
    }

    public function view(User $user, SalaryAdvance $advance): bool
    {
        return $user->hasPermission('payroll.finance.view', $advance->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payroll.loan.manage');
    }

    public function update(User $user, SalaryAdvance $advance): bool
    {
        return $user->hasPermission('payroll.loan.manage', $advance->organization);
    }

    public function approve(User $user, SalaryAdvance $advance): bool
    {
        return $user->hasPermission('payroll.loan.manage', $advance->organization);
    }

    public function reject(User $user, SalaryAdvance $advance): bool
    {
        return $user->hasPermission('payroll.loan.manage', $advance->organization);
    }
}
