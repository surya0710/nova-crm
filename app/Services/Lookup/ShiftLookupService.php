<?php

namespace App\Services\Lookup;

use App\Models\HrmsShift;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;

class ShiftLookupService extends AbstractLookupService
{
    protected function entityKey(): string
    {
        return 'shifts';
    }

    protected function modelClass(): string
    {
        return HrmsShift::class;
    }

    /**
     * @return list<string>
     */
    protected function searchColumns(): array
    {
        return ['name', 'code'];
    }

    protected function toResult(Model $model, Organization $organization): LookupResult
    {
        /** @var HrmsShift $model */
        $timeRange = collect([$model->start_time, $model->end_time])->filter()->implode(' – ');

        return new LookupResult(
            id: $model->id,
            label: $model->name,
            subtitle: $timeRange !== '' ? $timeRange : $model->code,
            badge: $model->is_default ? __('Default') : null,
            metadata: [
                'code' => $model->code,
                'start_time' => $model->start_time,
                'end_time' => $model->end_time,
            ],
        );
    }
}
