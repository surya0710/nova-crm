<?php

namespace App\Policies;

use App\Models\JobOpening;
use App\Models\User;

class JobOpeningPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('recruitment.view');
    }

    public function view(User $user, JobOpening $jobOpening): bool
    {
        return $user->hasPermission('recruitment.view', $jobOpening->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('recruitment.create');
    }

    public function update(User $user, JobOpening $jobOpening): bool
    {
        return $user->hasPermission('recruitment.edit', $jobOpening->organization);
    }

    public function delete(User $user, JobOpening $jobOpening): bool
    {
        return $user->hasPermission('recruitment.delete', $jobOpening->organization);
    }

    public function manage(User $user, ?JobOpening $jobOpening = null): bool
    {
        return $user->hasPermission('recruitment.manage', $jobOpening?->organization);
    }

    public function publish(User $user, JobOpening $jobOpening): bool
    {
        return $this->manage($user, $jobOpening);
    }
}
