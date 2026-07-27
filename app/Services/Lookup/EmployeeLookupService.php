<?php

namespace App\Services\Lookup;

use App\Models\Employee;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmployeeLookupService extends AbstractLookupService
{
    protected function entityKey(): string
    {
        return 'employees';
    }

    protected function modelClass(): string
    {
        return Employee::class;
    }

    protected function activeOnly(): bool
    {
        return true;
    }

    protected function activeColumn(): ?string
    {
        return null;
    }

    /**
     * @return list<string>
     */
    protected function searchColumns(): array
    {
        return ['first_name', 'last_name', 'employee_code', 'email', 'mobile'];
    }

    /**
     * @return list<string>
     */
    protected function eagerLoad(): array
    {
        return ['department', 'designation', 'branch'];
    }

    protected function orderByColumn(): string
    {
        return 'first_name';
    }

    protected function baseQuery(Organization $organization): Builder
    {
        return parent::baseQuery($organization)
            ->whereIn('status', ['active', 'on_probation']);
    }

    protected function toResult(Model $model, Organization $organization): LookupResult
    {
        /** @var Employee $model */
        $parts = array_filter([
            $model->employee_code,
            $model->designation?->name,
        ]);

        return new LookupResult(
            id: $model->id,
            label: $model->full_name,
            subtitle: $parts !== [] ? implode(' · ', $parts) : $model->email,
            badge: $model->department?->name,
            metadata: [
                'employee_code' => $model->employee_code,
                'email' => $model->email,
                'mobile' => $model->mobile,
                'department' => $model->department?->name,
                'designation' => $model->designation?->name,
                'branch' => $model->branch?->name,
                'user_id' => $model->user_id,
            ],
        );
    }
}
