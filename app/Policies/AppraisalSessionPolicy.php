<?php

namespace App\Policies;

use App\Models\AppraisalSession;
use App\Models\User;

class AppraisalSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('performance.appraisal.view');
    }

    public function view(User $user, AppraisalSession $session): bool
    {
        return $user->hasPermission('performance.appraisal.view', $session->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('performance.appraisal.manage');
    }

    public function update(User $user, AppraisalSession $session): bool
    {
        return $user->hasPermission('performance.appraisal.manage', $session->organization);
    }

    public function delete(User $user, AppraisalSession $session): bool
    {
        return $user->hasPermission('performance.appraisal.manage', $session->organization);
    }

    public function activate(User $user, AppraisalSession $session): bool
    {
        return $user->hasPermission('performance.appraisal.manage', $session->organization);
    }

    public function generate(User $user, AppraisalSession $session): bool
    {
        return $user->hasPermission('performance.appraisal.manage', $session->organization);
    }
}
