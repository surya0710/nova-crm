<?php

namespace App\Policies;

use App\Models\PayrollPeriod;
use App\Models\User;

class PayrollPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.view');
    }

    public function view(User $user, PayrollPeriod $payrollPeriod): bool
    {
        return $user->hasPermission('payroll.view', $payrollPeriod->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payroll.manage');
    }

    public function update(User $user, PayrollPeriod $payrollPeriod): bool
    {
        return $user->hasPermission('payroll.manage', $payrollPeriod->organization);
    }

    public function delete(User $user, PayrollPeriod $payrollPeriod): bool
    {
        return $user->hasPermission('payroll.manage', $payrollPeriod->organization);
    }

    public function lock(User $user, PayrollPeriod $payrollPeriod): bool
    {
        return $user->hasPermission('payroll.manage', $payrollPeriod->organization);
    }
}
