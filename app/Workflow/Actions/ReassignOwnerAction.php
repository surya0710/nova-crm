<?php

namespace App\Workflow\Actions;

use App\Services\Assignment\AssignmentService;
use App\Workflow\ActionContext;
use App\Workflow\Contracts\WorkflowActionHandler;

class ReassignOwnerAction implements WorkflowActionHandler
{
    public function __construct(protected AssignmentService $assignments) {}

    public function handle(ActionContext $context, array $configuration): array
    {
        $result = $this->assignments->assignOwner($context->subject, (int) $configuration['user_id'], $context->actor);

        return ['owner_id' => $result->assigneeId()];
    }
}
