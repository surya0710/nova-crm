<?php

namespace App\Workflow\Actions;

use App\Models\Customer;
use App\Services\CustomerLifecycleService;
use App\Workflow\ActionContext;
use App\Workflow\Contracts\WorkflowActionHandler;
use Illuminate\Validation\ValidationException;

class ChangeCustomerLifecycleAction implements WorkflowActionHandler
{
    public function __construct(protected CustomerLifecycleService $lifecycle) {}

    public function handle(ActionContext $context, array $configuration): array
    {
        $customer = $this->lifecycle->customerFrom($context->subject);
        if (! $customer instanceof Customer) {
            throw ValidationException::withMessages([
                'subject' => 'A customer is required to change lifecycle stage.',
            ]);
        }

        $stage = (string) ($configuration['lifecycle_stage'] ?? '');
        $customer = $this->lifecycle->changeStage($customer, $stage, $context->actor);

        return [
            'customer_id' => $customer->id,
            'lifecycle_stage' => $customer->lifecycle_stage,
        ];
    }
}
