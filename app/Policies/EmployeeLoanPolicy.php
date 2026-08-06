<?php

namespace App\Policies;

use App\Models\EmployeeLoan;
use App\Models\User;

class EmployeeLoanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.finance.view');
    }

    public function view(User $user, EmployeeLoan $loan): bool
    {
        return $user->hasPermission('payroll.finance.view', $loan->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payroll.loan.manage');
    }

    public function update(User $user, EmployeeLoan $loan): bool
    {
        return $user->hasPermission('payroll.loan.manage', $loan->organization);
    }

    public function close(User $user, EmployeeLoan $loan): bool
    {
        return $user->hasPermission('payroll.loan.manage', $loan->organization);
    }
}
