<?php

namespace App\Workflow\Actions;

use App\Services\ActivityService;
use App\Workflow\ActionContext;
use App\Workflow\Contracts\WorkflowActionHandler;

class CreateActivityAction implements WorkflowActionHandler
{
    public function __construct(protected ActivityService $activities) {}

    public function handle(ActionContext $context, array $configuration): array
    {
        $log = $this->activities->create(
            $context->subject,
            (string) $configuration['event'],
            $configuration['properties'] ?? [],
            $context->actor,
        );

        return ['audit_log_id' => $log->id];
    }
}
