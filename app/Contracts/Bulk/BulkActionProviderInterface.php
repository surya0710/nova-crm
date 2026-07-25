<?php

namespace App\Contracts\Bulk;

use App\Models\BulkOperation;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Module action providers plug into the Bulk Operations Framework.
 */
interface BulkActionProviderInterface
{
    /**
     * Stable action key, e.g. "lead.assign_owner".
     */
    public function key(): string;

    public function module(): string;

    public function entityType(): string;

    public function label(): string;

    /**
     * Module-specific permission required in addition to bulk.* scope.
     */
    public function permission(): string;

    public function confirmationMessage(): string;

    /**
     * Whether large selections should be queued.
     */
    public function supportsQueue(): bool;

    /**
     * Input fields required before execution.
     *
     * @return list<array{key: string, label: string, type: string, required?: bool, options?: array<string, string>}>
     */
    public function inputFields(): array;

    /**
     * Resolve organization-scoped query for selected records.
     *
     * @param  array{mode: string, ids?: list<int>, filters?: array<string, mixed>}  $selection
     */
    public function resolveQuery(Organization $organization, array $selection): Builder;

    /**
     * Execute the action for a single record.
     *
     * @param  array<string, mixed>  $input
     * @return array{status: 'success'|'skipped'|'failed', message?: string}
     */
    public function executeOne(Model $record, array $input, BulkOperation $operation): array;
}
