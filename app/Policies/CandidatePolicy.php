<?php

namespace App\Policies;

use App\Models\Candidate;
use App\Models\User;

class CandidatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('recruitment.view');
    }

    public function view(User $user, Candidate $candidate): bool
    {
        return $user->hasPermission('recruitment.view', $candidate->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('recruitment.create');
    }

    public function update(User $user, Candidate $candidate): bool
    {
        return $user->hasPermission('recruitment.edit', $candidate->organization);
    }

    public function delete(User $user, Candidate $candidate): bool
    {
        return $user->hasPermission('recruitment.delete', $candidate->organization);
    }
}
