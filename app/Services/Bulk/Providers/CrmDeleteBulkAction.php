<?php

namespace App\Services\Bulk\Providers;

use App\Contracts\Bulk\BulkActionProviderInterface;
use App\Models\BulkOperation;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Services\Bulk\Providers\Concerns\ResolvesBulkSelection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Soft-delete / archive style bulk delete for CRM entities.
 */
class CrmDeleteBulkAction implements BulkActionProviderInterface
{
    use ResolvesBulkSelection;

    public function __construct(
        protected string $entity,
        protected string $modelClass,
        protected string $permission,
        protected string $label = 'Delete',
    ) {}

    public function key(): string
    {
        return $this->entity.'.delete';
    }

    public function module(): string
    {
        return 'crm';
    }

    public function entityType(): string
    {
        return $this->entity;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function permission(): string
    {
        return $this->permission;
    }

    public function confirmationMessage(): string
    {
        return 'Permanently delete the selected records? This cannot be undone from bulk operations.';
    }

    public function supportsQueue(): bool
    {
        return true;
    }

    public function inputFields(): array
    {
        return [];
    }

    public function resolveQuery(Organization $organization, array $selection): Builder
    {
        return $this->baseOrganizationQuery($this->modelClass, $organization, $selection);
    }

    public function executeOne(Model $record, array $input, BulkOperation $operation): array
    {
        $record->delete();

        return $this->success();
    }

    public static function leads(): self
    {
        return new self('lead', Lead::class, 'leads.delete');
    }

    public static function customers(): self
    {
        return new self('customer', Customer::class, 'customers.delete');
    }

    public static function opportunities(): self
    {
        return new self('opportunity', Opportunity::class, 'opportunities.delete');
    }
}
