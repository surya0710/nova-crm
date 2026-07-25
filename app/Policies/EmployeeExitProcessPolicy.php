<?php

namespace App\Policies;

use App\Models\EmployeeExitProcess;
use App\Models\User;

class EmployeeExitProcessPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('employee.exit.manage');
    }

    public function view(User $user, EmployeeExitProcess $exitProcess): bool
    {
        return $user->hasPermission('employee.exit.manage', $exitProcess->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('employee.exit.manage');
    }

    public function update(User $user, EmployeeExitProcess $exitProcess): bool
    {
        return $user->hasPermission('employee.exit.manage', $exitProcess->organization);
    }
}
