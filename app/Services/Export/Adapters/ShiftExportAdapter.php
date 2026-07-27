<?php

namespace App\Services\Export\Adapters;

use App\Models\HrmsShift;
use App\Services\Export\ExportColumnDefinition;

class ShiftExportAdapter extends AbstractExportAdapter
{
    public function entityType(): string
    {
        return 'shift';
    }

    public function entityLabel(): string
    {
        return 'Shifts';
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
        return HrmsShift::class;
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('id', 'ID', ExportColumnDefinition::TYPE_NUMBER, default: false, hidden: true),
            new ExportColumnDefinition('code', 'Code'),
            new ExportColumnDefinition('name', 'Name'),
            new ExportColumnDefinition('start_time', 'Start Time'),
            new ExportColumnDefinition('end_time', 'End Time'),
            new ExportColumnDefinition('working_hours', 'Working Hours', ExportColumnDefinition::TYPE_NUMBER, default: false),
            new ExportColumnDefinition('is_overnight', 'Overnight', ExportColumnDefinition::TYPE_BOOLEAN, default: false),
            new ExportColumnDefinition('is_active', 'Active', ExportColumnDefinition::TYPE_BOOLEAN),
            new ExportColumnDefinition('is_default', 'Default', ExportColumnDefinition::TYPE_BOOLEAN, default: false),
        ];
    }
}
