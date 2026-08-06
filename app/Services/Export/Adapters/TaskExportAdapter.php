<?php

namespace App\Services\Export\Adapters;

use App\Models\Task;
use App\Services\Export\ExportColumnDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class TaskExportAdapter extends AbstractExportAdapter
{
    public function entityType(): string
    {
        return 'task';
    }

    public function entityLabel(): string
    {
        return 'Tasks';
    }

    public function module(): string
    {
        return 'projects';
    }

    public function permission(): string
    {
        return 'tasks.view';
    }

    protected function modelClass(): string
    {
        return Task::class;
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('id', 'ID', ExportColumnDefinition::TYPE_NUMBER, default: false, hidden: true),
            new ExportColumnDefinition('task_number', 'Task #', default: false),
            new ExportColumnDefinition('title', 'Title'),
            new ExportColumnDefinition('project', 'Project', ExportColumnDefinition::TYPE_RELATIONSHIP, eager: ['project']),
            new ExportColumnDefinition('status', 'Status'),
            new ExportColumnDefinition('priority', 'Priority'),
            new ExportColumnDefinition('progress', 'Progress %', ExportColumnDefinition::TYPE_NUMBER, attribute: 'completion_percentage', default: false),
            new ExportColumnDefinition('assignee', 'Assignee', ExportColumnDefinition::TYPE_RELATIONSHIP, eager: ['assignee']),
            new ExportColumnDefinition('due_date', 'Due Date', ExportColumnDefinition::TYPE_DATE, default: false),
            new ExportColumnDefinition('created_at', 'Created At', ExportColumnDefinition::TYPE_DATETIME, default: false),
        ];
    }

    protected function computedOverrides(Model $record): array
    {
        /** @var Task $record */
        return [
            'project' => $record->project?->name ?? '',
            'assignee' => $record->assignee?->name ?? '',
        ];
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        parent::applyFilters($query, $filters);

        if ($search = Arr::get($filters, 'search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($priority = Arr::get($filters, 'priority')) {
            $query->where('priority', $priority);
        }
    }
}
