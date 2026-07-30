<?php

namespace App\Services\Export\Adapters;

use App\Models\Customer;
use App\Services\CustomerService;
use App\Services\Export\ExportColumnDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CustomerExportAdapter extends AbstractExportAdapter
{
    public function __construct(protected CustomerService $customerService) {}

    public function entityType(): string
    {
        return 'customer';
    }

    public function entityLabel(): string
    {
        return 'Customers';
    }

    public function module(): string
    {
        return 'crm';
    }

    public function permission(): string
    {
        return 'customers.view';
    }

    protected function modelClass(): string
    {
        return Customer::class;
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('id', 'ID', ExportColumnDefinition::TYPE_NUMBER, default: false, hidden: true),
            new ExportColumnDefinition('display_name', 'Name', attribute: 'name'),
            new ExportColumnDefinition('company', 'Company', default: false),
            new ExportColumnDefinition('email', 'Email'),
            new ExportColumnDefinition('phone', 'Phone'),
            new ExportColumnDefinition('state', 'State'),
            new ExportColumnDefinition('country', 'Country'),
            new ExportColumnDefinition('status', 'Status'),
            new ExportColumnDefinition('assigned_owner', 'Assigned Owner', ExportColumnDefinition::TYPE_RELATIONSHIP, eager: ['assignee']),
            new ExportColumnDefinition('industry', 'Industry', default: false),
            new ExportColumnDefinition('created_at', 'Created At', ExportColumnDefinition::TYPE_DATETIME, default: false),
        ];
    }

    protected function computedOverrides(Model $record): array
    {
        /** @var Customer $record */
        return [
            'display_name' => $record->display_name ?? $record->name ?? '',
            'assigned_owner' => $record->assignee?->name
                ?? (method_exists($record, 'owner') ? $record->owner?->name : null)
                ?? '',
        ];
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        parent::applyFilters($query, $filters);

        if ($search = Arr::get($filters, 'search')) {
            $this->customerService->searchQuery($query, (string) $search);
        }

        $this->customerService->geographicFilterQuery(
            $query,
            Arr::get($filters, 'state'),
            Arr::get($filters, 'country'),
        );
    }
}
