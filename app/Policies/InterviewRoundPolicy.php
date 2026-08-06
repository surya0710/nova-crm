<?php

namespace App\Policies;

use App\Models\InterviewRound;
use App\Models\User;

class InterviewRoundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('recruitment.interview.view');
    }

    public function view(User $user, InterviewRound $round): bool
    {
        return $user->hasPermission('recruitment.interview.view', $round->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('recruitment.interview.create');
    }

    public function update(User $user, InterviewRound $round): bool
    {
        return $user->hasPermission('recruitment.interview.edit', $round->organization);
    }

    public function delete(User $user, InterviewRound $round): bool
    {
        return $user->hasPermission('recruitment.interview.delete', $round->organization);
    }

    public function complete(User $user, InterviewRound $round): bool
    {
        return $user->hasPermission('recruitment.interview.edit', $round->organization);
    }

    public function cancel(User $user, InterviewRound $round): bool
    {
        return $user->hasPermission('recruitment.interview.edit', $round->organization);
    }
}
