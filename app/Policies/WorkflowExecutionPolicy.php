<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkflowExecution;

class WorkflowExecutionPolicy
{
    public function view(User $user, WorkflowExecution $execution): bool
    {
        return $user->hasPermission('workflows.view', $execution->organization);
    }
}
