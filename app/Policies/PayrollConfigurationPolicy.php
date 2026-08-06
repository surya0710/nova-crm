<?php

namespace App\Policies;

use App\Models\PayrollConfiguration;
use App\Models\User;

class PayrollConfigurationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.view') || $user->hasPermission('payroll.configuration');
    }

    public function view(User $user, PayrollConfiguration $configuration): bool
    {
        return $user->hasPermission('payroll.view', $configuration->organization)
            || $user->hasPermission('payroll.configuration', $configuration->organization);
    }

    public function update(User $user, ?PayrollConfiguration $configuration = null): bool
    {
        if ($configuration) {
            return $user->hasPermission('payroll.configuration', $configuration->organization);
        }

        return $user->hasPermission('payroll.configuration');
    }
}
