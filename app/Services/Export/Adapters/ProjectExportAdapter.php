<?php

namespace App\Services\Export\Adapters;

use App\Models\Project;
use App\Services\Export\ExportColumnDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ProjectExportAdapter extends AbstractExportAdapter
{
    public function entityType(): string
    {
        return 'project';
    }

    public function entityLabel(): string
    {
        return 'Projects';
    }

    public function module(): string
    {
        return 'projects';
    }

    public function permission(): string
    {
        return 'projects.view';
    }

    protected function modelClass(): string
    {
        return Project::class;
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('id', 'ID', ExportColumnDefinition::TYPE_NUMBER, default: false, hidden: true),
            new ExportColumnDefinition('project_number', 'Project #'),
            new ExportColumnDefinition('name', 'Name'),
            new ExportColumnDefinition('priority', 'Priority'),
            new ExportColumnDefinition('status', 'Status', ExportColumnDefinition::TYPE_RELATIONSHIP, eager: ['status']),
            new ExportColumnDefinition('progress', 'Progress %', ExportColumnDefinition::TYPE_NUMBER, attribute: 'completion_percentage'),
            new ExportColumnDefinition('owner', 'Owner', ExportColumnDefinition::TYPE_RELATIONSHIP, eager: ['owner']),
            new ExportColumnDefinition('members', 'Members', ExportColumnDefinition::TYPE_COMPUTED, default: false, eager: ['members.user']),
            new ExportColumnDefinition('start_date', 'Start Date', ExportColumnDefinition::TYPE_DATE, default: false),
            new ExportColumnDefinition('planned_end_date', 'Planned End', ExportColumnDefinition::TYPE_DATE, default: false),
            new ExportColumnDefinition('is_archived', 'Archived', ExportColumnDefinition::TYPE_BOOLEAN, default: false, hidden: true),
        ];
    }

    protected function computedOverrides(Model $record): array
    {
        /** @var Project $record */
        $memberNames = '';
        if ($record->relationLoaded('members')) {
            $memberNames = $record->members
                ->map(fn ($m) => $m->user?->name ?? '')
                ->filter()
                ->implode(', ');
        }

        return [
            'status' => $record->status?->name ?? $record->status?->label ?? '',
            'owner' => $record->owner?->name ?? '',
            'members' => $memberNames,
        ];
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if ($search = Arr::get($filters, 'search')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('project_number', 'like', "%{$search}%");
            });
        }

        if ($priority = Arr::get($filters, 'priority')) {
            $query->where('priority', $priority);
        }
    }
}
