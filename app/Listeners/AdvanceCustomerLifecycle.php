<?php

namespace App\Listeners;

use App\Events\WorkflowDomainEvent;
use App\Services\CustomerLifecycleService;

class AdvanceCustomerLifecycle
{
    public function __construct(protected CustomerLifecycleService $lifecycle) {}

    public function handle(WorkflowDomainEvent $event): void
    {
        if (! array_key_exists($event->trigger(), config('customer_lifecycle.milestones', []))) {
            return;
        }

        $this->lifecycle->handle($event);
    }
}
