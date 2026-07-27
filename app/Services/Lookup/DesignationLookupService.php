<?php

namespace App\Services\Lookup;

use App\Models\Designation;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;

class DesignationLookupService extends AbstractLookupService
{
    protected function entityKey(): string
    {
        return 'designations';
    }

    protected function modelClass(): string
    {
        return Designation::class;
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
        return ['department'];
    }

    protected function toResult(Model $model, Organization $organization): LookupResult
    {
        /** @var Designation $model */
        return new LookupResult(
            id: $model->id,
            label: $model->name,
            subtitle: $model->code,
            badge: $model->department?->name,
            metadata: [
                'code' => $model->code,
                'level' => $model->level,
                'department' => $model->department?->name,
            ],
        );
    }
}
