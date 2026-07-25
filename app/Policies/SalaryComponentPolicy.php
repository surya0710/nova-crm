<?php

namespace App\Policies;

use App\Models\SalaryComponent;
use App\Models\User;

class SalaryComponentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.view');
    }

    public function view(User $user, SalaryComponent $salaryComponent): bool
    {
        return $user->hasPermission('payroll.view', $salaryComponent->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payroll.manage');
    }

    public function update(User $user, SalaryComponent $salaryComponent): bool
    {
        return $user->hasPermission('payroll.manage', $salaryComponent->organization);
    }

    public function delete(User $user, SalaryComponent $salaryComponent): bool
    {
        return $user->hasPermission('payroll.manage', $salaryComponent->organization);
    }
}
