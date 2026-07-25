<?php

namespace App\Policies;

use App\Models\PayrollRun;
use App\Models\User;

class PayrollRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.view');
    }

    public function view(User $user, PayrollRun $payrollRun): bool
    {
        return $user->hasPermission('payroll.view', $payrollRun->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payroll.calculate') || $user->hasPermission('payroll.manage');
    }

    public function calculate(User $user, PayrollRun $payrollRun): bool
    {
        return $user->hasPermission('payroll.calculate', $payrollRun->organization)
            || $user->hasPermission('payroll.manage', $payrollRun->organization);
    }

    public function recalculate(User $user, PayrollRun $payrollRun): bool
    {
        return $this->calculate($user, $payrollRun);
    }

    public function approve(User $user, PayrollRun $payrollRun): bool
    {
        return $user->hasPermission('payroll.approve', $payrollRun->organization)
            || $user->hasPermission('payroll.manage', $payrollRun->organization);
    }

    public function publish(User $user, PayrollRun $payrollRun): bool
    {
        return $user->hasPermission('payroll.publish', $payrollRun->organization)
            || $user->hasPermission('payroll.manage', $payrollRun->organization);
    }

    public function reverse(User $user, PayrollRun $payrollRun): bool
    {
        return $user->hasPermission('payroll.finance.manage', $payrollRun->organization);
    }
}
