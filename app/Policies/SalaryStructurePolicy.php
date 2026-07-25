<?php

namespace App\Policies;

use App\Models\SalaryStructure;
use App\Models\User;

class SalaryStructurePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.view');
    }

    public function view(User $user, SalaryStructure $salaryStructure): bool
    {
        return $user->hasPermission('payroll.view', $salaryStructure->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payroll.manage');
    }

    public function update(User $user, SalaryStructure $salaryStructure): bool
    {
        return $user->hasPermission('payroll.manage', $salaryStructure->organization);
    }

    public function delete(User $user, SalaryStructure $salaryStructure): bool
    {
        return $user->hasPermission('payroll.manage', $salaryStructure->organization);
    }
}
