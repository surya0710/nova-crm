<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use App\Services\Hrms\EssContext;

class EmployeePolicy
{
    public function __construct(protected EssContext $essContext) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('hrms.view');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $this->viewOwn($user, $employee)
            || $user->hasPermission('hrms.view', $employee->organization);
    }

    public function viewOwn(User $user, Employee $employee): bool
    {
        return $user->hasPermission('ess.access', $employee->organization)
            && (int) $employee->user_id === (int) $user->id;
    }

    public function viewTeam(User $user, Employee $employee): bool
    {
        if (! $user->hasPermission('manager.dashboard', $employee->organization)) {
            return false;
        }

        $manager = $this->essContext->employeeFor($user);

        return $manager !== null && $this->essContext->managesEmployee($manager, $employee);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('hrms.create');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $this->updateOwn($user, $employee)
            || $user->hasPermission('hrms.update', $employee->organization);
    }

    public function updateOwn(User $user, Employee $employee): bool
    {
        return $this->viewOwn($user, $employee);
    }

    public function clock(User $user, Employee $employee): bool
    {
        return $this->viewOwn($user, $employee);
    }

    public function applyLeave(User $user, Employee $employee): bool
    {
        return $this->viewOwn($user, $employee);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasPermission('hrms.manage', $employee->organization);
    }

    public function manage(User $user, Employee $employee): bool
    {
        return $user->hasPermission('hrms.manage', $employee->organization);
    }
}
