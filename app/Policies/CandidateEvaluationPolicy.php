<?php

namespace App\Policies;

use App\Models\CandidateEvaluation;
use App\Models\User;

class CandidateEvaluationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('recruitment.interview.view')
            || $user->hasPermission('recruitment.evaluate');
    }

    public function view(User $user, CandidateEvaluation $evaluation): bool
    {
        return $user->hasPermission('recruitment.interview.view', $evaluation->organization)
            || $user->hasPermission('recruitment.evaluate', $evaluation->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('recruitment.evaluate');
    }

    public function update(User $user, CandidateEvaluation $evaluation): bool
    {
        return $user->hasPermission('recruitment.evaluate', $evaluation->organization);
    }
}
