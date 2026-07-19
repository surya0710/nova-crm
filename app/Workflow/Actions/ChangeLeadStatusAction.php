<?php

namespace App\Workflow\Actions;

use App\Models\Lead;
use App\Services\LeadService;
use App\Workflow\ActionContext;
use App\Workflow\Contracts\WorkflowActionHandler;

class ChangeLeadStatusAction implements WorkflowActionHandler
{
    public function __construct(protected LeadService $leads) {}

    public function handle(ActionContext $context, array $configuration): array
    {
        /** @var Lead $lead */
        $lead = $context->subject;
        $lead = $this->leads->changeStatus($lead, (string) $configuration['status'], $context->actor);

        return ['status' => $lead->status];
    }
}
