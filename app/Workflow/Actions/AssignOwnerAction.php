<?php

namespace App\Workflow\Actions;

use App\Services\Assignment\AssignmentService;
use App\Workflow\ActionContext;
use App\Workflow\Contracts\WorkflowActionHandler;

class AssignOwnerAction implements WorkflowActionHandler
{
    public function __construct(protected AssignmentService $assignments) {}

    public function handle(ActionContext $context, array $configuration): array
    {
        $result = $this->assignments->assignOwner($context->subject, null, $context->actor, automatic: true);

        return [
            'owner_id' => $result->assigneeId() ?? $context->subject->fresh()?->getAttribute('assigned_to'),
            'strategy' => $result->strategy,
        ];
    }
}
