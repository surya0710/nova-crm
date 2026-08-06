<?php

namespace App\Services\Export\Adapters;

use App\Models\ProjectMilestone;
use App\Services\Export\ExportColumnDefinition;
use Illuminate\Database\Eloquent\Model;

class MilestoneExportAdapter extends AbstractExportAdapter
{
    public function entityType(): string
    {
        return 'milestone';
    }

    public function entityLabel(): string
    {
        return 'Milestones';
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
        return ProjectMilestone::class;
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('id', 'ID', ExportColumnDefinition::TYPE_NUMBER, default: false, hidden: true),
            new ExportColumnDefinition('project', 'Project', ExportColumnDefinition::TYPE_RELATIONSHIP, eager: ['project']),
            new ExportColumnDefinition('name', 'Name'),
            new ExportColumnDefinition('status', 'Status'),
            new ExportColumnDefinition('sequence', 'Sequence', ExportColumnDefinition::TYPE_NUMBER, default: false),
            new ExportColumnDefinition('due_date', 'Due Date', ExportColumnDefinition::TYPE_DATE),
            new ExportColumnDefinition('completed_at', 'Completed At', ExportColumnDefinition::TYPE_DATETIME, default: false),
        ];
    }

    protected function computedOverrides(Model $record): array
    {
        return ['project' => $record->project?->name ?? ''];
    }
}
