<?php

namespace App\Policies;

use App\Models\JobApplication;
use App\Models\User;

class JobApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('recruitment.view');
    }

    public function view(User $user, JobApplication $jobApplication): bool
    {
        return $user->hasPermission('recruitment.view', $jobApplication->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('recruitment.create');
    }

    public function update(User $user, JobApplication $jobApplication): bool
    {
        return $user->hasPermission('recruitment.edit', $jobApplication->organization);
    }

    public function delete(User $user, JobApplication $jobApplication): bool
    {
        return $user->hasPermission('recruitment.delete', $jobApplication->organization);
    }
}
