<?php

namespace App\Services\Export\Adapters;

use App\Models\Role;
use App\Services\Export\ExportColumnDefinition;

class RoleExportAdapter extends AbstractExportAdapter
{
    public function entityType(): string
    {
        return 'role';
    }

    public function entityLabel(): string
    {
        return 'Roles';
    }

    public function module(): string
    {
        return 'administration';
    }

    public function permission(): string
    {
        return 'users.manage';
    }

    protected function modelClass(): string
    {
        return Role::class;
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('id', 'ID', ExportColumnDefinition::TYPE_NUMBER, default: false, hidden: true),
            new ExportColumnDefinition('name', 'Name'),
            new ExportColumnDefinition('slug', 'Slug'),
            new ExportColumnDefinition('description', 'Description', default: false),
            new ExportColumnDefinition('hierarchy_level', 'Hierarchy Level', ExportColumnDefinition::TYPE_NUMBER, default: false),
            new ExportColumnDefinition('is_system', 'System Role', ExportColumnDefinition::TYPE_BOOLEAN),
            new ExportColumnDefinition('is_default', 'Default', ExportColumnDefinition::TYPE_BOOLEAN, default: false),
            new ExportColumnDefinition('is_active', 'Active', ExportColumnDefinition::TYPE_BOOLEAN),
        ];
    }
}
