<?php

namespace App\Services\Lookup;

use App\Contracts\Lookup\LookupProviderInterface;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractLookupService implements LookupProviderInterface
{
    abstract protected function entityKey(): string;

    public function key(): string
    {
        return $this->entityKey();
    }

    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    /**
     * @return list<string>
     */
    abstract protected function searchColumns(): array;

    abstract protected function toResult(Model $model, Organization $organization): LookupResult;

    protected function organizationColumn(): string
    {
        return 'organization_id';
    }

    protected function activeOnly(): bool
    {
        return true;
    }

    protected function activeColumn(): ?string
    {
        return 'is_active';
    }

    /**
     * @return list<string>
     */
    protected function eagerLoad(): array
    {
        return [];
    }

    protected function baseQuery(Organization $organization): Builder
    {
        $query = $this->modelClass()::query()
            ->where($this->organizationColumn(), $organization->id);

        if ($this->activeOnly() && $this->activeColumn()) {
            $query->where($this->activeColumn(), true);
        }

        $with = $this->eagerLoad();
        if ($with !== []) {
            $query->with($with);
        }

        return $query;
    }

    protected function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%'.mb_strtolower($search).'%';
        $table = $builder->getModel()->getTable();

        $builder->where(function (Builder $inner) use ($like, $table) {
            foreach ($this->searchColumns() as $column) {
                if (str_contains($column, '.')) {
                    [$relation, $field] = explode('.', $column, 2);
                    $inner->orWhereHas($relation, function (Builder $related) use ($field, $like) {
                        $related->whereRaw('LOWER('.$related->getModel()->getTable().'.'.$field.') like ?', [$like]);
                    });
                } else {
                    $inner->orWhereRaw('LOWER('.$table.'.'.$column.') like ?', [$like]);
                }
            }
        });
    }

    protected function orderByColumn(): string
    {
        return 'name';
    }

    public function search(
        Organization $organization,
        User $actor,
        string $query,
        int $page,
        int $perPage,
    ): LookupPaginatedResult {
        $search = trim($query);
        $builder = $this->baseQuery($organization);
        $this->applySearch($builder, $search);

        $orderColumn = $this->orderByColumn();
        $table = $builder->getModel()->getTable();
        if (! str_contains($orderColumn, '.')) {
            $builder->orderBy($table.'.'.$orderColumn);
        } else {
            $builder->orderBy($orderColumn);
        }

        $paginator = $builder->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())
            ->map(fn (Model $model) => $this->toResult($model, $organization))
            ->values()
            ->all();

        return new LookupPaginatedResult(
            items: $items,
            page: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            hasMore: $paginator->hasMorePages(),
        );
    }

    public function findOne(Organization $organization, int|string $id): ?LookupResult
    {
        $model = $this->baseQuery($organization)->whereKey($id)->first();

        return $model ? $this->toResult($model, $organization) : null;
    }
}
