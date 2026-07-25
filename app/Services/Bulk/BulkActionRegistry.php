<?php

namespace App\Services\Bulk;

use App\Contracts\Bulk\BulkActionProviderInterface;
use InvalidArgumentException;

class BulkActionRegistry
{
    /** @var array<string, BulkActionProviderInterface> */
    protected array $actions = [];

    public function register(BulkActionProviderInterface $action): void
    {
        $key = $action->key();

        if ($key === '') {
            throw new InvalidArgumentException('Bulk action key cannot be empty.');
        }

        $this->actions[$key] = $action;
    }

    public function has(string $key): bool
    {
        return isset($this->actions[$key]);
    }

    public function resolve(string $key): BulkActionProviderInterface
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException("Bulk action [{$key}] is not registered.");
        }

        return $this->actions[$key];
    }

    /**
     * @return list<BulkActionProviderInterface>
     */
    public function all(): array
    {
        return array_values($this->actions);
    }

    /**
     * @return list<BulkActionProviderInterface>
     */
    public function forEntity(string $entityType): array
    {
        return array_values(array_filter(
            $this->actions,
            static fn (BulkActionProviderInterface $action): bool => $action->entityType() === $entityType
        ));
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function catalogGrouped(): array
    {
        $groups = [];

        foreach ($this->actions as $action) {
            $groups[$action->module()][] = [
                'key' => $action->key(),
                'label' => $action->label(),
                'entity_type' => $action->entityType(),
                'permission' => $action->permission(),
                'requires_input' => $action->inputFields() !== [],
                'supports_queue' => $action->supportsQueue(),
                'confirmation' => $action->confirmationMessage(),
                'input_fields' => $action->inputFields(),
            ];
        }

        return $groups;
    }
}
