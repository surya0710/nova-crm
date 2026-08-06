<?php

namespace App\Services\Export\Adapters;

use App\Models\Opportunity;
use App\Services\Export\ExportColumnDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class OpportunityExportAdapter extends AbstractExportAdapter
{
    public function entityType(): string
    {
        return 'opportunity';
    }

    public function entityLabel(): string
    {
        return 'Opportunities';
    }

    public function module(): string
    {
        return 'crm';
    }

    public function permission(): string
    {
        return 'opportunities.view';
    }

    protected function modelClass(): string
    {
        return Opportunity::class;
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('id', 'ID', ExportColumnDefinition::TYPE_NUMBER, default: false, hidden: true),
            new ExportColumnDefinition('title', 'Title'),
            new ExportColumnDefinition('customer', 'Customer', ExportColumnDefinition::TYPE_RELATIONSHIP, eager: ['customer']),
            new ExportColumnDefinition('stage', 'Stage'),
            new ExportColumnDefinition('amount', 'Amount', ExportColumnDefinition::TYPE_NUMBER),
            new ExportColumnDefinition('currency', 'Currency', default: false),
            new ExportColumnDefinition('probability', 'Probability', ExportColumnDefinition::TYPE_NUMBER, default: false),
            new ExportColumnDefinition('expected_close_date', 'Expected Close', ExportColumnDefinition::TYPE_DATE),
            new ExportColumnDefinition('assigned_owner', 'Assigned Owner', ExportColumnDefinition::TYPE_RELATIONSHIP, eager: ['assignee']),
            new ExportColumnDefinition('created_at', 'Created At', ExportColumnDefinition::TYPE_DATETIME, default: false),
        ];
    }

    protected function computedOverrides(Model $record): array
    {
        /** @var Opportunity $record */
        return [
            'customer' => $record->customer?->name ?? '',
            'assigned_owner' => $record->assignee?->name ?? '',
        ];
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        parent::applyFilters($query, $filters);

        if ($search = Arr::get($filters, 'search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($stage = Arr::get($filters, 'stage')) {
            $query->where('stage', $stage);
        }
    }
}
