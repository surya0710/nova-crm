<?php

namespace App\Services\Export;

use App\Contracts\Export\ExportableEntityInterface;
use InvalidArgumentException;

/**
 * Registry of entity adapters that plug into the Export Platform.
 */
class ExportDefinitionRegistry
{
    /** @var array<string, ExportableEntityInterface> */
    protected array $entities = [];

    public function register(ExportableEntityInterface $entity): void
    {
        $type = $entity->entityType();

        if ($type === '') {
            throw new InvalidArgumentException('Exportable entity type cannot be empty.');
        }

        $this->entities[$type] = $entity;
    }

    public function has(string $entityType): bool
    {
        return isset($this->entities[$entityType]);
    }

    public function resolve(string $entityType): ExportableEntityInterface
    {
        if (! $this->has($entityType)) {
            throw new InvalidArgumentException("Exportable entity [{$entityType}] is not registered.");
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
     * @return list<array{type: string, label: string, module: string, column_count: int}>
     */
    public function catalog(): array
    {
        $catalog = [];

        foreach ($this->entities as $entity) {
            $catalog[] = [
                'type' => $entity->entityType(),
                'label' => $entity->entityLabel(),
                'module' => $entity->module(),
                'column_count' => count($entity->columnDefinitions()),
            ];
        }

        return $catalog;
    }
}
