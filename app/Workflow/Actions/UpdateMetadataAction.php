<?php

namespace App\Workflow\Actions;

use App\Events\CustomerUpdated;
use App\Events\LeadUpdated;
use App\Services\MetadataEntityFormService;
use App\Workflow\ActionContext;
use App\Workflow\Contracts\WorkflowActionHandler;
use App\Workflow\WorkflowRuntimeContext;

class UpdateMetadataAction implements WorkflowActionHandler
{
    public function __construct(
        protected MetadataEntityFormService $metadataForms,
    ) {}

    public function handle(ActionContext $context, array $configuration): array
    {
        $subject = $context->subject::withoutGlobalScopes()
            ->where('organization_id', $context->execution->organization_id)
            ->whereKey($context->subject->getKey())
            ->lockForUpdate()
            ->firstOrFail();
        $entityType = strtolower(class_basename($subject));
        $validated = $this->metadataForms->validatedValues(
            $subject,
            $subject->organization,
            $entityType,
            (array) $configuration['values'],
            allowUnknown: false,
            enforceRequired: false,
        );
        $result = $this->metadataForms->persistValidatedValues($subject, $validated)
            ?? ['changed' => false, 'changes' => []];
        if ($result['changed']) {
            $runtime = app(WorkflowRuntimeContext::class);
            $eventClass = match ($entityType) {
                'lead' => LeadUpdated::class,
                'customer' => CustomerUpdated::class,
                default => null,
            };
            if ($eventClass) {
                event($eventClass::forModel(
                    $subject->fresh(),
                    ['actor_id' => $context->actor->id, 'changes' => array_keys($result['changes'])],
                    causationId: $runtime->causationId,
                    depth: $runtime->depth + 1,
                ));
            }
        }

        return ['changed' => $result['changed'], 'changes' => array_keys($result['changes'])];
    }
}
