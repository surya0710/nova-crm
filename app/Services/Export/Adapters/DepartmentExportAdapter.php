<?php

namespace App\Services\Export\Adapters;

use App\Models\Department;
use App\Services\Export\ExportColumnDefinition;
use Illuminate\Database\Eloquent\Model;

class DepartmentExportAdapter extends AbstractExportAdapter
{
    public function entityType(): string
    {
        return 'department';
    }

    public function entityLabel(): string
    {
        return 'Departments';
    }

    public function module(): string
    {
        return 'hrms';
    }

    public function permission(): string
    {
        return 'hrms.view';
    }

    protected function modelClass(): string
    {
        return Department::class;
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('id', 'ID', ExportColumnDefinition::TYPE_NUMBER, default: false, hidden: true),
            new ExportColumnDefinition('code', 'Code'),
            new ExportColumnDefinition('name', 'Name'),
            new ExportColumnDefinition('description', 'Description', default: false),
            new ExportColumnDefinition('branch', 'Branch', ExportColumnDefinition::TYPE_RELATIONSHIP, eager: ['branch']),
            new ExportColumnDefinition('is_active', 'Active', ExportColumnDefinition::TYPE_BOOLEAN),
        ];
    }

    protected function computedOverrides(Model $record): array
    {
        return ['branch' => $record->branch?->name ?? ''];
    }
}
