<?php

namespace App\Policies;

use App\Models\PerformanceConfiguration;
use App\Models\User;

class PerformanceConfigurationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('performance.view') || $user->hasPermission('performance.configuration');
    }

    public function view(User $user, PerformanceConfiguration $configuration): bool
    {
        return $user->hasPermission('performance.view', $configuration->organization)
            || $user->hasPermission('performance.configuration', $configuration->organization);
    }

    public function update(User $user, ?PerformanceConfiguration $configuration = null): bool
    {
        if ($configuration) {
            return $user->hasPermission('performance.configuration', $configuration->organization);
        }

        return $user->hasPermission('performance.configuration');
    }
}
