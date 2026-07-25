<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('hrms.view');
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->hasPermission('hrms.view', $branch->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('hrms.create');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->hasPermission('hrms.update', $branch->organization);
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->hasPermission('hrms.manage', $branch->organization);
    }

    public function manage(User $user, Branch $branch): bool
    {
        return $user->hasPermission('hrms.manage', $branch->organization);
    }
}
