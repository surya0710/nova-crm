<?php

namespace App\Policies;

use App\Models\JobRequisition;
use App\Models\User;

class JobRequisitionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('recruitment.view');
    }

    public function view(User $user, JobRequisition $jobRequisition): bool
    {
        return $user->hasPermission('recruitment.view', $jobRequisition->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('recruitment.create');
    }

    public function update(User $user, JobRequisition $jobRequisition): bool
    {
        return $user->hasPermission('recruitment.edit', $jobRequisition->organization);
    }

    public function delete(User $user, JobRequisition $jobRequisition): bool
    {
        return $user->hasPermission('recruitment.delete', $jobRequisition->organization);
    }

    public function manage(User $user, ?JobRequisition $jobRequisition = null): bool
    {
        return $user->hasPermission('recruitment.manage', $jobRequisition?->organization);
    }

    public function submit(User $user, JobRequisition $jobRequisition): bool
    {
        return $this->update($user, $jobRequisition);
    }

    public function approve(User $user, JobRequisition $jobRequisition): bool
    {
        return $this->manage($user, $jobRequisition);
    }
}
