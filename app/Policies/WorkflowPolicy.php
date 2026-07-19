<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workflow;

class WorkflowPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('workflows.view');
    }

    public function view(User $user, Workflow $workflow): bool
    {
        return $user->hasPermission('workflows.view', $workflow->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('workflows.create');
    }

    public function update(User $user, Workflow $workflow): bool
    {
        return $user->hasPermission('workflows.update', $workflow->organization);
    }

    public function delete(User $user, Workflow $workflow): bool
    {
        return $user->hasPermission('workflows.delete', $workflow->organization);
    }

    public function manage(User $user, Workflow $workflow): bool
    {
        return $user->hasPermission('workflows.manage', $workflow->organization);
    }
}
