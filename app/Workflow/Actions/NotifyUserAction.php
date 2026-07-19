<?php

namespace App\Workflow\Actions;

use App\Services\NotificationService;
use App\Workflow\ActionContext;
use App\Workflow\Contracts\WorkflowActionHandler;

class NotifyUserAction implements WorkflowActionHandler
{
    public function __construct(protected NotificationService $notifications) {}

    public function handle(ActionContext $context, array $configuration): array
    {
        $this->notifications->send(
            $context->execution->organization_id,
            (int) $configuration['user_id'],
            (string) $configuration['title'],
            (string) $configuration['message'],
            $configuration['action_url'] ?? null,
        );

        return ['notified_user_id' => (int) $configuration['user_id']];
    }
}
