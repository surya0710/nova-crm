<?php

namespace App\Services\Export\Adapters;

use App\Models\Branch;
use App\Services\Export\ExportColumnDefinition;

class BranchExportAdapter extends AbstractExportAdapter
{
    public function entityType(): string
    {
        return 'branch';
    }

    public function entityLabel(): string
    {
        return 'Branches';
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
        return Branch::class;
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('id', 'ID', ExportColumnDefinition::TYPE_NUMBER, default: false, hidden: true),
            new ExportColumnDefinition('code', 'Code'),
            new ExportColumnDefinition('name', 'Name'),
            new ExportColumnDefinition('city', 'City', default: false),
            new ExportColumnDefinition('state', 'State', default: false),
            new ExportColumnDefinition('country', 'Country', default: false),
            new ExportColumnDefinition('is_active', 'Active', ExportColumnDefinition::TYPE_BOOLEAN),
        ];
    }
}
