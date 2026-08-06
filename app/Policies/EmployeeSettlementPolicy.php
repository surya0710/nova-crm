<?php

namespace App\Policies;

use App\Models\EmployeeSettlement;
use App\Models\User;

class EmployeeSettlementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.finance.view');
    }

    public function view(User $user, EmployeeSettlement $settlement): bool
    {
        return $user->hasPermission('payroll.finance.view', $settlement->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payroll.settlement.manage');
    }
}
