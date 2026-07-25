<?php

namespace App\Policies;

use App\Models\ExpenseReimbursement;
use App\Models\User;

class ExpenseReimbursementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.finance.view');
    }

    public function view(User $user, ExpenseReimbursement $reimbursement): bool
    {
        return $user->hasPermission('payroll.finance.view', $reimbursement->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payroll.loan.manage');
    }

    public function update(User $user, ExpenseReimbursement $reimbursement): bool
    {
        return $user->hasPermission('payroll.loan.manage', $reimbursement->organization);
    }

    public function approve(User $user, ExpenseReimbursement $reimbursement): bool
    {
        return $user->hasPermission('payroll.loan.manage', $reimbursement->organization);
    }

    public function reject(User $user, ExpenseReimbursement $reimbursement): bool
    {
        return $user->hasPermission('payroll.loan.manage', $reimbursement->organization);
    }
}
