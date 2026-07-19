<?php

namespace App\Workflow;

use App\Workflow\Contracts\WorkflowActionHandler;
use Illuminate\Contracts\Container\Container;
use Illuminate\Validation\ValidationException;

class ActionDispatcher
{
    public function __construct(protected Container $container) {}

    public function dispatch(ActionContext $context): array
    {
        $organizationId = (int) $context->execution->organization_id;
        if ((int) $context->subject->getAttribute('organization_id') !== $organizationId
            || (int) $context->action->organization_id !== $organizationId
            || ! $context->subject->organization?->users()->whereKey($context->actor->id)->exists()) {
            throw ValidationException::withMessages(['action' => 'Workflow action context crosses organization boundaries.']);
        }

        $definition = config("workflows.actions.{$context->action->type}");

        if (! is_array($definition)) {
            throw ValidationException::withMessages(['action' => "Unknown workflow action [{$context->action->type}]."]);
        }

        $entity = strtolower(class_basename($context->subject));
        if (! in_array($entity, $definition['entities'], true)) {
            throw ValidationException::withMessages(['action' => "Action [{$context->action->type}] does not support {$entity}."]);
        }

        foreach ($definition['fields'] as $field) {
            if (! array_key_exists($field, $context->action->configuration)
                || $context->action->configuration[$field] === null
                || $context->action->configuration[$field] === ''
                || $context->action->configuration[$field] === []) {
                throw ValidationException::withMessages(["configuration.{$field}" => "The {$field} field is required."]);
            }
        }

        $allowed = array_keys($definition['form_fields'] ?? []);
        foreach (array_keys($context->action->configuration) as $field) {
            if (! in_array($field, $allowed, true)) {
                throw ValidationException::withMessages(["configuration.{$field}" => "Unsupported configuration field [{$field}]."]);
            }
        }

        $handler = $this->container->make($definition['handler']);
        if (! $handler instanceof WorkflowActionHandler) {
            throw new \LogicException('Workflow action handler must implement '.WorkflowActionHandler::class);
        }

        return $handler->handle($context, $context->action->configuration);
    }
}
