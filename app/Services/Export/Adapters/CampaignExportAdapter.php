<?php

namespace App\Services\Export\Adapters;

use App\Models\MarketingCampaign;
use App\Services\Export\ExportColumnDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CampaignExportAdapter extends AbstractExportAdapter
{
    public function entityType(): string
    {
        return 'campaign';
    }

    public function entityLabel(): string
    {
        return 'Campaigns';
    }

    public function module(): string
    {
        return 'marketing';
    }

    public function permission(): string
    {
        return 'marketing.view';
    }

    protected function modelClass(): string
    {
        return MarketingCampaign::class;
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('id', 'ID', ExportColumnDefinition::TYPE_NUMBER, default: false, hidden: true),
            new ExportColumnDefinition('name', 'Name'),
            new ExportColumnDefinition('slug', 'Slug', default: false),
            new ExportColumnDefinition('status', 'Status'),
            new ExportColumnDefinition('budget_amount', 'Budget', ExportColumnDefinition::TYPE_NUMBER, default: false),
            new ExportColumnDefinition('budget_currency', 'Currency', default: false),
            new ExportColumnDefinition('utm_campaign', 'UTM Campaign', default: false),
            new ExportColumnDefinition('channels', 'Channels', default: false),
            new ExportColumnDefinition('starts_at', 'Starts At', ExportColumnDefinition::TYPE_DATETIME, default: false),
            new ExportColumnDefinition('ends_at', 'Ends At', ExportColumnDefinition::TYPE_DATETIME, default: false),
            new ExportColumnDefinition('created_by_name', 'Created By', ExportColumnDefinition::TYPE_RELATIONSHIP, eager: ['creator']),
        ];
    }

    protected function computedOverrides(Model $record): array
    {
        /** @var MarketingCampaign $record */
        return [
            'created_by_name' => $record->creator?->name ?? '',
        ];
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        parent::applyFilters($query, $filters);

        if ($search = Arr::get($filters, 'search')) {
            $query->where('name', 'like', "%{$search}%");
        }
    }
}
