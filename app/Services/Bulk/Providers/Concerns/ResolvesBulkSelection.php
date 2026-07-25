<?php

namespace App\Services\Bulk\Providers\Concerns;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

trait ResolvesBulkSelection
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

        if ($mode === 'ids' || $mode === 'page') {
            if ($ids === []) {
                throw new InvalidArgumentException('At least one record must be selected.');
            }

            return $query->whereIn($key, $ids);
        }

        if ($mode === 'all' || $mode === 'filtered') {
            $filters = $selection['filters'] ?? [];
            if (method_exists($this, 'applyFilters')) {
                $this->applyFilters($query, $filters);
            }

            if ($ids !== []) {
                $query->whereIn($key, $ids);
            }

            return $query;
        }

        throw new InvalidArgumentException("Unsupported selection mode [{$mode}].");
    }

    /**
     * @return array{status: 'success'|'skipped'|'failed', message?: string}
     */
    protected function success(?string $message = null): array
    {
        return array_filter([
            'status' => 'success',
            'message' => $message,
        ], static fn ($v) => $v !== null);
    }

    /**
     * @return array{status: 'success'|'skipped'|'failed', message?: string}
     */
    protected function skipped(string $message): array
    {
        return ['status' => 'skipped', 'message' => $message];
    }

    /**
     * @return array{status: 'success'|'skipped'|'failed', message?: string}
     */
    protected function failed(string $message): array
    {
        return ['status' => 'failed', 'message' => $message];
    }
}
