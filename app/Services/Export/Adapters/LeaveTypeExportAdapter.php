<?php

namespace App\Services\Export\Adapters;

use App\Models\LeaveType;
use App\Services\Export\ExportColumnDefinition;

class LeaveTypeExportAdapter extends AbstractExportAdapter
{
    public function entityType(): string
    {
        return 'leave_type';
    }

    public function entityLabel(): string
    {
        return 'Leave Types';
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
        return LeaveType::class;
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('id', 'ID', ExportColumnDefinition::TYPE_NUMBER, default: false, hidden: true),
            new ExportColumnDefinition('code', 'Code'),
            new ExportColumnDefinition('name', 'Name'),
            new ExportColumnDefinition('is_paid', 'Paid', ExportColumnDefinition::TYPE_BOOLEAN),
            new ExportColumnDefinition('requires_approval', 'Requires Approval', ExportColumnDefinition::TYPE_BOOLEAN),
            new ExportColumnDefinition('allocation_days', 'Allocation Days', ExportColumnDefinition::TYPE_NUMBER, default: false),
            new ExportColumnDefinition('max_days_per_year', 'Max Days / Year', ExportColumnDefinition::TYPE_NUMBER, default: false),
            new ExportColumnDefinition('is_active', 'Active', ExportColumnDefinition::TYPE_BOOLEAN),
        ];
    }
}
