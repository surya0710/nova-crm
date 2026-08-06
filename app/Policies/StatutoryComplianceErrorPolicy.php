<?php

namespace App\Policies;

use App\Models\StatutoryComplianceError;
use App\Models\User;

class StatutoryComplianceErrorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.statutory.view')
            || $user->hasPermission('payroll.statutory.manage');
    }

    public function view(User $user, StatutoryComplianceError $error): bool
    {
        return $user->hasPermission('payroll.statutory.view', $error->organization)
            || $user->hasPermission('payroll.statutory.manage', $error->organization);
    }

    public function validate(User $user): bool
    {
        return $user->hasPermission('payroll.statutory.manage')
            || $user->hasPermission('payroll.statutory.configuration');
    }
}
