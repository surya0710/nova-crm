<?php

namespace App\Policies;

use App\Models\PayrollAdjustment;
use App\Models\User;

class PayrollAdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.view')
            || $user->hasPermission('payroll.adjustment.manage');
    }

    public function view(User $user, PayrollAdjustment $adjustment): bool
    {
        return $user->hasPermission('payroll.view', $adjustment->organization)
            || $user->hasPermission('payroll.adjustment.manage', $adjustment->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payroll.adjustment.manage')
            || $user->hasPermission('payroll.manage');
    }

    public function update(User $user, PayrollAdjustment $adjustment): bool
    {
        return $user->hasPermission('payroll.adjustment.manage', $adjustment->organization)
            || $user->hasPermission('payroll.manage', $adjustment->organization);
    }

    public function approve(User $user, PayrollAdjustment $adjustment): bool
    {
        return $user->hasPermission('payroll.adjustment.approve', $adjustment->organization)
            || $user->hasPermission('payroll.approve', $adjustment->organization)
            || $user->hasPermission('payroll.manage', $adjustment->organization);
    }

    public function delete(User $user, PayrollAdjustment $adjustment): bool
    {
        return $this->update($user, $adjustment);
    }
}
