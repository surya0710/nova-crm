<?php

namespace App\Policies;

use App\Models\InterviewStage;
use App\Models\User;

class InterviewStagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('recruitment.interview.view');
    }

    public function view(User $user, InterviewStage $stage): bool
    {
        return $user->hasPermission('recruitment.interview.view', $stage->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('recruitment.interview.create');
    }

    public function update(User $user, InterviewStage $stage): bool
    {
        return $user->hasPermission('recruitment.interview.edit', $stage->organization);
    }

    public function delete(User $user, InterviewStage $stage): bool
    {
        return $user->hasPermission('recruitment.interview.delete', $stage->organization);
    }
}
