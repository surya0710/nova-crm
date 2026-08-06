<?php

namespace App\Contracts\Import;

use App\Models\ImportSession;
use App\Services\Import\ImportFieldDefinition;

/**
 * Contract that business entities implement to plug into the Import Platform.
 *
 * Entities provide field definitions and a persistence callback.
 * The Import Platform owns parsing, mapping, validation, preview, and sessions.
 */
interface ImportableEntityInterface
{
    /**
     * Stable entity type key used on import sessions (e.g. "lead", "customer").
     */
    public function entityType(): string;

    /**
     * Human-readable label for the entity type.
     */
    public function entityLabel(): string;

    /**
     * Field definitions the Import Platform uses for mapping and validation.
     *
     * @return list<ImportFieldDefinition>
     */
    public function fieldDefinitions(): array;

    /**
     * Entity-specific validation after generic type validation.
     *
     * Used for duplicates, owner resolution, lookup resolution, etc.
     * Return additional row-level errors; an empty list means no extras.
     *
     * @param  list<array{row_number: int, values: array<string, mixed>, valid: bool, errors: list<string>}>  $rows
     * @return list<array{row_number: int, column: string|null, field: string|null, error: string, value: string|null}>
     */
    public function validateMappedRows(array $rows, ImportSession $session): array;

    /**
     * Persist a single validated, mapped row.
     *
     * Invoked by ImportPlatformService::executeImport().
     *
     * @param  array<string, mixed>  $mappedRow  Keys are field definition keys
     * @return array{action: 'created'|'updated'|'skipped', id?: int|string|null}
     */
    public function persistRow(array $mappedRow, ImportSession $session): array;
}
