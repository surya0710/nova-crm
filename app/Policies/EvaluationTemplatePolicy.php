<?php

namespace App\Policies;

use App\Models\EvaluationTemplate;
use App\Models\User;

class EvaluationTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('recruitment.interview.view');
    }

    public function view(User $user, EvaluationTemplate $template): bool
    {
        return $user->hasPermission('recruitment.interview.view', $template->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('recruitment.interview.create');
    }

    public function update(User $user, EvaluationTemplate $template): bool
    {
        return $user->hasPermission('recruitment.interview.edit', $template->organization);
    }

    public function delete(User $user, EvaluationTemplate $template): bool
    {
        return $user->hasPermission('recruitment.interview.delete', $template->organization);
    }
}
