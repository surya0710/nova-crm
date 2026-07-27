<?php

namespace App\Contracts\Export;

use App\Models\Organization;
use App\Services\Export\ExportColumnDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Module adapters plug into the Export Platform by registering column definitions
 * and a scoped query builder. The platform owns formats, queueing, and downloads.
 */
interface ExportableEntityInterface
{
    /**
     * Stable entity type key (e.g. "lead", "employee").
     */
    public function entityType(): string;

    /**
     * Human-readable label for the entity type.
     */
    public function entityLabel(): string;

    /**
     * Module key for catalog grouping (crm, hrms, projects, …).
     */
    public function module(): string;

    /**
     * Module permission required in addition to exports.* scope.
     */
    public function permission(): string;

    /**
     * Exportable column definitions (never include secrets).
     *
     * @return list<ExportColumnDefinition>
     */
    public function columnDefinitions(): array;

    /**
     * Default column keys when the user does not customize selection.
     *
     * @return list<string>
     */
    public function defaultColumns(): array;

    /**
     * Column keys that exist for advanced use but are hidden from the default UI.
     *
     * @return list<string>
     */
    public function hiddenColumns(): array;

    /**
     * Resolve organization-scoped query for the selection.
     *
     * @param  array{mode: string, ids?: list<int>, filters?: array<string, mixed>}  $selection
     */
    public function resolveQuery(Organization $organization, array $selection): Builder;

    /**
     * Map a model instance to an associative row keyed by column definition keys.
     *
     * @param  list<string>  $columns
     * @return array<string, mixed>
     */
    public function mapRow(Model $record, array $columns): array;

    /**
     * Eager-load relations needed for the selected columns.
     *
     * @param  list<string>  $columns
     * @return list<string>
     */
    public function eagerLoads(array $columns): array;
}
