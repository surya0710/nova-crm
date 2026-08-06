<?php

namespace App\Services\Lookup;

use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;

class BranchLookupService extends AbstractLookupService
{
    protected function entityKey(): string
    {
        return 'branches';
    }

    protected function modelClass(): string
    {
        return Branch::class;
    }

    /**
     * @return list<string>
     */
    protected function searchColumns(): array
    {
        return ['name', 'code', 'city'];
    }

    protected function toResult(Model $model, Organization $organization): LookupResult
    {
        /** @var Branch $model */
        $subtitle = collect([$model->code, $model->city])->filter()->implode(' · ');

        return new LookupResult(
            id: $model->id,
            label: $model->name,
            subtitle: $subtitle !== '' ? $subtitle : null,
            badge: $model->is_default ? __('Default') : null,
            metadata: [
                'code' => $model->code,
                'city' => $model->city,
            ],
        );
    }
}
