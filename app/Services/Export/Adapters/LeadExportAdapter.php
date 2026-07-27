<?php

namespace App\Services\Export\Adapters;

use App\Models\Lead;
use App\Services\Export\ExportColumnDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class LeadExportAdapter extends AbstractExportAdapter
{
    public function entityType(): string
    {
        return 'lead';
    }

    public function entityLabel(): string
    {
        return 'Leads';
    }

    public function module(): string
    {
        return 'crm';
    }

    public function permission(): string
    {
        return 'leads.view';
    }

    protected function modelClass(): string
    {
        return Lead::class;
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('id', 'ID', ExportColumnDefinition::TYPE_NUMBER, default: false, hidden: true, attribute: 'id'),
            new ExportColumnDefinition('name', 'Name'),
            new ExportColumnDefinition('company', 'Company'),
            new ExportColumnDefinition('email', 'Email'),
            new ExportColumnDefinition('phone', 'Phone'),
            new ExportColumnDefinition('source', 'Source'),
            new ExportColumnDefinition('industry', 'Industry', default: false),
            new ExportColumnDefinition('budget', 'Budget', ExportColumnDefinition::TYPE_NUMBER, default: false),
            new ExportColumnDefinition('priority', 'Priority'),
            new ExportColumnDefinition('status', 'Status'),
            new ExportColumnDefinition('assigned_owner', 'Assigned Owner', ExportColumnDefinition::TYPE_RELATIONSHIP, eager: ['assignee']),
            new ExportColumnDefinition('next_follow_up_at', 'Next Follow-up', ExportColumnDefinition::TYPE_DATETIME, default: false),
            new ExportColumnDefinition('tags', 'Tags', default: false),
            new ExportColumnDefinition('timeline_summary', 'Timeline Summary', ExportColumnDefinition::TYPE_COMPUTED, default: false, eager: ['notes']),
            new ExportColumnDefinition('created_at', 'Created At', ExportColumnDefinition::TYPE_DATETIME, default: false),
            new ExportColumnDefinition('updated_at', 'Updated At', ExportColumnDefinition::TYPE_DATETIME, default: false, hidden: true),
        ];
    }

    protected function computedOverrides(Model $record): array
    {
        /** @var Lead $record */
        $notesCount = $record->relationLoaded('notes') ? $record->notes->count() : null;

        return [
            'assigned_owner' => $record->assignee?->name ?? '',
            'timeline_summary' => $notesCount !== null
                ? sprintf('%d notes · status %s', $notesCount, $record->status)
                : (string) $record->status,
        ];
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        parent::applyFilters($query, $filters);

        if ($search = Arr::get($filters, 'search')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($assigned = Arr::get($filters, 'assigned_to')) {
            $query->where('assigned_to', (int) $assigned);
        }
    }
}
