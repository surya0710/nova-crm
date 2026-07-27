<?php

namespace App\Services\Lookup;

use App\Models\Department;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;

class DepartmentLookupService extends AbstractLookupService
{
    protected function entityKey(): string
    {
        return 'departments';
    }

    protected function modelClass(): string
    {
        return Department::class;
    }

    /**
     * @return list<string>
     */
    protected function searchColumns(): array
    {
        return ['name', 'code'];
    }

    /**
     * @return list<string>
     */
    protected function eagerLoad(): array
    {
        return ['branch'];
    }

    protected function toResult(Model $model, Organization $organization): LookupResult
    {
        /** @var Department $model */
        $subtitle = $model->code ?: $model->branch?->name;

        return new LookupResult(
            id: $model->id,
            label: $model->name,
            subtitle: $subtitle,
            badge: $model->branch?->name,
            metadata: [
                'code' => $model->code,
                'branch' => $model->branch?->name,
            ],
        );
    }
}
