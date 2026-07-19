<?php

namespace App\Workflow\Contracts;

use App\Workflow\ActionContext;

interface WorkflowActionHandler
{
    /** @return array<string, mixed> */
    public function handle(ActionContext $context, array $configuration): array;
}
