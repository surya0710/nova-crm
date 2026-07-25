<?php

namespace App\Policies;

use App\Models\Kpi;
use App\Models\User;

class KpiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('performance.goal.view');
    }

    public function view(User $user, Kpi $kpi): bool
    {
        return $user->hasPermission('performance.goal.view', $kpi->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('performance.goal.manage');
    }

    public function update(User $user, Kpi $kpi): bool
    {
        return $user->hasPermission('performance.goal.manage', $kpi->organization);
    }

    public function delete(User $user, Kpi $kpi): bool
    {
        return $user->hasPermission('performance.goal.manage', $kpi->organization);
    }
}
