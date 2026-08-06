<?php

namespace App\Workflow\Actions;

use App\Services\TaskService;
use App\Workflow\ActionContext;
use App\Workflow\Contracts\WorkflowActionHandler;

class CreateTaskAction implements WorkflowActionHandler
{
    public function __construct(protected TaskService $tasks) {}

    public function handle(ActionContext $context, array $configuration): array
    {
        $task = $this->tasks->createFor($context->subject, $configuration, $context->actor);

        return ['task_id' => $task->id];
    }
}
