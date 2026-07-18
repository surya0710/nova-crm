<?php

namespace App\Contracts\Import;

use App\Models\Organization;
use App\Services\Import\ImportTemplateColumn;
use App\Services\Import\ImportTemplateLookupGroup;

/**
 * Entity-specific contract for dynamic import template generation.
 *
 * ImportTemplateService stays entity-agnostic; adapters supply columns,
 * sample values, lookup groups, and instructions for a tenant.
 */
interface ImportTemplateProviderInterface
{
    /**
     * Stable entity type key (e.g. "lead", "customer").
     */
    public function entityType(): string;

    /**
     * Human-readable entity label used in filenames and sheet titles.
     */
    public function entityLabel(): string;

    /**
     * Primary data worksheet name for Excel templates.
     */
    public function dataSheetName(): string;

    /**
     * Ordered template columns (standard first, then metadata).
     *
     * @return list<ImportTemplateColumn>
     */
    public function columns(Organization $organization): array;

    /**
     * One realistic sample row keyed by column key.
     *
     * @return array<string, string|null>
     */
    public function sampleValues(Organization $organization): array;

    /**
     * Lookup sections for the Excel Lookup Values sheet.
     *
     * @return list<ImportTemplateLookupGroup>
     */
    public function lookupGroups(Organization $organization): array;

    /**
     * Concise instruction lines for the Excel Instructions sheet.
     *
     * @return list<string>
     */
    public function instructionLines(Organization $organization): array;
}
