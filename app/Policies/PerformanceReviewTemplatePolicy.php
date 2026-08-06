<?php

namespace App\Policies;

use App\Models\PerformanceReviewTemplate;
use App\Models\User;

class PerformanceReviewTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('performance.view');
    }

    public function view(User $user, PerformanceReviewTemplate $template): bool
    {
        return $user->hasPermission('performance.view', $template->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('performance.manage');
    }

    public function update(User $user, PerformanceReviewTemplate $template): bool
    {
        return $user->hasPermission('performance.manage', $template->organization);
    }

    public function delete(User $user, PerformanceReviewTemplate $template): bool
    {
        return $user->hasPermission('performance.manage', $template->organization);
    }
}
