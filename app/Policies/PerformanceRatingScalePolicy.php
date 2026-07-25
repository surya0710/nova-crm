<?php

namespace App\Policies;

use App\Models\PerformanceRatingScale;
use App\Models\User;

class PerformanceRatingScalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('performance.view');
    }

    public function view(User $user, PerformanceRatingScale $scale): bool
    {
        return $user->hasPermission('performance.view', $scale->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('performance.manage');
    }

    public function update(User $user, PerformanceRatingScale $scale): bool
    {
        return $user->hasPermission('performance.manage', $scale->organization);
    }

    public function delete(User $user, PerformanceRatingScale $scale): bool
    {
        return $user->hasPermission('performance.manage', $scale->organization);
    }
}
