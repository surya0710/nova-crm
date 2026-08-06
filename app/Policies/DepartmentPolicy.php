<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('hrms.view');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->hasPermission('hrms.view', $department->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('hrms.create');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->hasPermission('hrms.update', $department->organization);
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->hasPermission('hrms.manage', $department->organization);
    }

    public function manage(User $user, Department $department): bool
    {
        return $user->hasPermission('hrms.manage', $department->organization);
    }
}
