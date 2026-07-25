<?php

namespace App\Policies;

use App\Models\Competency;
use App\Models\User;

class CompetencyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('performance.view');
    }

    public function view(User $user, Competency $competency): bool
    {
        return $user->hasPermission('performance.view', $competency->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('performance.manage');
    }

    public function update(User $user, Competency $competency): bool
    {
        return $user->hasPermission('performance.manage', $competency->organization);
    }

    public function delete(User $user, Competency $competency): bool
    {
        return $user->hasPermission('performance.manage', $competency->organization);
    }
}
