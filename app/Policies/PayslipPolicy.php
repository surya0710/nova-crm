<?php

namespace App\Policies;

use App\Models\Payslip;
use App\Models\User;
use App\Services\Hrms\EssContext;

class PayslipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payslip.view')
            || $user->hasPermission('payroll.view')
            || $user->hasPermission('ess.access');
    }

    public function view(User $user, Payslip $payslip): bool
    {
        if ($user->hasPermission('payslip.view', $payslip->organization)
            || $user->hasPermission('payroll.view', $payslip->organization)) {
            return true;
        }

        if (! $user->hasPermission('ess.access', $payslip->organization)
            && ! $user->hasPermission('payslip.view', $payslip->organization)) {
            return false;
        }

        $employee = app(EssContext::class)->employeeFor($user);

        return $employee && (int) $employee->id === (int) $payslip->employee_id;
    }

    public function download(User $user, Payslip $payslip): bool
    {
        if ($user->hasPermission('payslip.download', $payslip->organization)
            || $user->hasPermission('payroll.view', $payslip->organization)) {
            return true;
        }

        if (! $user->hasPermission('ess.access', $payslip->organization)
            && ! $user->hasPermission('payslip.download', $payslip->organization)) {
            return false;
        }

        $employee = app(EssContext::class)->employeeFor($user);

        return $employee && (int) $employee->id === (int) $payslip->employee_id;
    }

    public function email(User $user, Payslip $payslip): bool
    {
        return $user->hasPermission('payroll.publish', $payslip->organization)
            || $user->hasPermission('payroll.manage', $payslip->organization);
    }
}
