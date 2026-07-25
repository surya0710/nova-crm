<?php

namespace App\Policies;

use App\Models\PerformanceCycle;
use App\Models\User;

class PerformanceCyclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('performance.view');
    }

    public function view(User $user, PerformanceCycle $cycle): bool
    {
        return $user->hasPermission('performance.view', $cycle->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('performance.manage');
    }

    public function update(User $user, PerformanceCycle $cycle): bool
    {
        return $user->hasPermission('performance.manage', $cycle->organization);
    }

    public function delete(User $user, PerformanceCycle $cycle): bool
    {
        return $user->hasPermission('performance.manage', $cycle->organization);
    }
}
