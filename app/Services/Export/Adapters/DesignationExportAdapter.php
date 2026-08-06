<?php

namespace App\Services\Export\Adapters;

use App\Models\Designation;
use App\Services\Export\ExportColumnDefinition;

class DesignationExportAdapter extends AbstractExportAdapter
{
    public function entityType(): string
    {
        return 'designation';
    }

    public function entityLabel(): string
    {
        return 'Designations';
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
        return Designation::class;
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('id', 'ID', ExportColumnDefinition::TYPE_NUMBER, default: false, hidden: true),
            new ExportColumnDefinition('code', 'Code', default: false),
            new ExportColumnDefinition('name', 'Name'),
            new ExportColumnDefinition('description', 'Description', default: false),
            new ExportColumnDefinition('is_active', 'Active', ExportColumnDefinition::TYPE_BOOLEAN),
        ];
    }
}
