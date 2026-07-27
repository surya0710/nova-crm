<?php

namespace App\Services\Export\Adapters;

use App\Models\Holiday;
use App\Services\Export\ExportColumnDefinition;
use Illuminate\Database\Eloquent\Model;

class HolidayExportAdapter extends AbstractExportAdapter
{
    public function entityType(): string
    {
        return 'holiday';
    }

    public function entityLabel(): string
    {
        return 'Holidays';
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
        return Holiday::class;
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('id', 'ID', ExportColumnDefinition::TYPE_NUMBER, default: false, hidden: true),
            new ExportColumnDefinition('name', 'Name'),
            new ExportColumnDefinition('holiday_date', 'Date', ExportColumnDefinition::TYPE_DATE),
            new ExportColumnDefinition('branch', 'Branch', ExportColumnDefinition::TYPE_RELATIONSHIP, default: false, eager: ['branch']),
            new ExportColumnDefinition('is_optional', 'Optional', ExportColumnDefinition::TYPE_BOOLEAN),
            new ExportColumnDefinition('is_recurring', 'Recurring', ExportColumnDefinition::TYPE_BOOLEAN, default: false),
        ];
    }

    protected function computedOverrides(Model $record): array
    {
        return ['branch' => $record->branch?->name ?? 'Organization-wide'];
    }
}
