<?php

namespace App\Services\Import;

use App\Contracts\Import\ImportableEntityInterface;
use InvalidArgumentException;

/**
 * Registry of entity adapters that plug into the Import Platform.
 *
 * Future modules register adapters here without modifying platform internals.
 */
class ImportEntityRegistry
{
    /** @var array<string, ImportableEntityInterface> */
    protected array $entities = [];

    public function register(ImportableEntityInterface $entity): void
    {
        $type = $entity->entityType();

        if ($type === '') {
            throw new InvalidArgumentException('Importable entity type cannot be empty.');
        }

        $this->entities[$type] = $entity;
    }

    public function has(string $entityType): bool
    {
        return isset($this->entities[$entityType]);
    }

    public function resolve(string $entityType): ImportableEntityInterface
    {
        if (! $this->has($entityType)) {
            throw new InvalidArgumentException("Importable entity [{$entityType}] is not registered.");
        }

        return $this->entities[$entityType];
    }

    /**
     * @return list<string>
     */
    public function types(): array
    {
        return array_keys($this->entities);
    }

    /**
     * @return list<array{type: string, label: string, field_count: int}>
     */
    public function catalog(): array
    {
        $catalog = [];

        foreach ($this->entities as $entity) {
            $catalog[] = [
                'type' => $entity->entityType(),
                'label' => $entity->entityLabel(),
                'field_count' => count($entity->fieldDefinitions()),
            ];
        }

        return $catalog;
    }
}
