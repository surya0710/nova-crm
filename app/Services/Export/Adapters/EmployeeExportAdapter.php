<?php

namespace App\Services\Export\Adapters;

use App\Models\Employee;
use App\Services\Export\ExportColumnDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EmployeeExportAdapter extends AbstractExportAdapter
{
    public function entityType(): string
    {
        return 'employee';
    }

    public function entityLabel(): string
    {
        return 'Employees';
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
        return Employee::class;
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('id', 'ID', ExportColumnDefinition::TYPE_NUMBER, default: false, hidden: true),
            new ExportColumnDefinition('employee_code', 'Employee Code'),
            new ExportColumnDefinition('first_name', 'First Name'),
            new ExportColumnDefinition('last_name', 'Last Name'),
            new ExportColumnDefinition('email', 'Work Email'),
            new ExportColumnDefinition('mobile', 'Mobile', default: false),
            new ExportColumnDefinition('status', 'Employment Status'),
            new ExportColumnDefinition('employment_type', 'Employment Type', default: false),
            new ExportColumnDefinition('department', 'Department', ExportColumnDefinition::TYPE_RELATIONSHIP, eager: ['department']),
            new ExportColumnDefinition('designation', 'Designation', ExportColumnDefinition::TYPE_RELATIONSHIP, eager: ['designation']),
            new ExportColumnDefinition('branch', 'Branch', ExportColumnDefinition::TYPE_RELATIONSHIP, eager: ['branch']),
            new ExportColumnDefinition('manager', 'Manager', ExportColumnDefinition::TYPE_RELATIONSHIP, eager: ['reportingManager']),
            new ExportColumnDefinition('joining_date', 'Joining Date', ExportColumnDefinition::TYPE_DATE),
            new ExportColumnDefinition('portal_status', 'Portal Status', ExportColumnDefinition::TYPE_COMPUTED, eager: ['user']),
            new ExportColumnDefinition('created_at', 'Created At', ExportColumnDefinition::TYPE_DATETIME, default: false, hidden: true),
        ];
    }

    protected function computedOverrides(Model $record): array
    {
        /** @var Employee $record */
        $user = $record->user;

        return [
            'department' => $record->department?->name ?? '',
            'designation' => $record->designation?->name ?? '',
            'branch' => $record->branch?->name ?? '',
            'manager' => $record->reportingManager
                ? trim(($record->reportingManager->first_name ?? '').' '.($record->reportingManager->last_name ?? ''))
                : '',
            'portal_status' => $user
                ? ($user->portal_access_enabled ? 'Enabled' : 'Disabled').' · '.$user->displayAccountStatusLabel()
                : 'No login',
        ];
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        parent::applyFilters($query, $filters);

        if ($search = Arr::get($filters, 'search')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        if ($departmentId = Arr::get($filters, 'department_id')) {
            $query->where('department_id', (int) $departmentId);
        }
    }
}
