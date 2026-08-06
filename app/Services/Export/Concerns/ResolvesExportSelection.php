<?php

namespace App\Services\Export\Concerns;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

trait ResolvesExportSelection
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  array{mode?: string, ids?: list<int|string>, filters?: array<string, mixed>}  $selection
     */
    protected function baseOrganizationQuery(string $modelClass, Organization $organization, array $selection): Builder
    {
        if ($modelClass === User::class) {
            $query = User::query()->whereHas(
                'organizations',
                fn (Builder $q) => $q->where('organizations.id', $organization->id)
            );
            $table = (new User)->getTable();
        } else {
            /** @var Model $model */
            $model = new $modelClass;
            $table = $model->getTable();
            $query = $modelClass::query()->where($table.'.organization_id', $organization->id);
        }

        $mode = $selection['mode'] ?? 'ids';
        $ids = array_values(array_unique(array_map('intval', $selection['ids'] ?? [])));
        $key = $table.'.'.(new $modelClass)->getKeyName();

        if ($mode === 'ids' || $mode === 'page' || $mode === 'selected') {
            if ($ids === []) {
                throw new InvalidArgumentException('At least one record must be selected.');
            }

            return $query->whereIn($key, $ids);
        }

        if ($mode === 'all' || $mode === 'filtered' || $mode === 'complete') {
            $filters = $selection['filters'] ?? [];
            if (method_exists($this, 'applyFilters')) {
                $this->applyFilters($query, $filters);
            }

            if ($ids !== [] && in_array($mode, ['filtered'], true)) {
                // Optional intersection when filters + ids provided.
            }

            return $query;
        }

        throw new InvalidArgumentException("Unsupported export selection mode [{$mode}].");
    }
}
