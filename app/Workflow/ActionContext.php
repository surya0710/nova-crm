<?php

namespace App\Workflow;

use App\Models\User;
use App\Models\WorkflowAction;
use App\Models\WorkflowExecution;
use Illuminate\Database\Eloquent\Model;

final readonly class ActionContext
{
    public function __construct(
        public WorkflowExecution $execution,
        public WorkflowAction $action,
        public Model $subject,
        public User $actor,
    ) {}
}
