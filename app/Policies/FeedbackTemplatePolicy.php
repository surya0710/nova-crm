<?php

namespace App\Policies;

use App\Models\FeedbackTemplate;
use App\Models\User;

class FeedbackTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('performance.feedback.view');
    }

    public function view(User $user, FeedbackTemplate $template): bool
    {
        return $user->hasPermission('performance.feedback.view', $template->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('performance.feedback.manage');
    }

    public function update(User $user, FeedbackTemplate $template): bool
    {
        return $user->hasPermission('performance.feedback.manage', $template->organization);
    }

    public function delete(User $user, FeedbackTemplate $template): bool
    {
        return $user->hasPermission('performance.feedback.manage', $template->organization);
    }
}
