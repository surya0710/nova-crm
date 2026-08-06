<?php

namespace App\Policies;

use App\Models\EmployeeSalaryAssignment;
use App\Models\User;

class EmployeeSalaryAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.view');
    }

    public function view(User $user, EmployeeSalaryAssignment $assignment): bool
    {
        return $user->hasPermission('payroll.view', $assignment->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payroll.manage');
    }

    public function update(User $user, EmployeeSalaryAssignment $assignment): bool
    {
        return $user->hasPermission('payroll.manage', $assignment->organization);
    }

    public function delete(User $user, EmployeeSalaryAssignment $assignment): bool
    {
        return false;
    }
}
