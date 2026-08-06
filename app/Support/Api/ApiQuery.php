<?php

namespace App\Support\Api;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ApiQuery
{
    public static function perPage(Request $request, int $default = 20, int $max = 100): int
    {
        return min($max, max(1, (int) $request->input('per_page', $default)));
    }

    /**
     * Apply allowlisted equality / like filters.
     *
     * @param  array<string, string>  $allowed  map of request key => column (prefix with like: for partial match)
     */
    public static function applyFilters(Builder $query, Request $request, array $allowed): Builder
    {
        foreach ($allowed as $input => $column) {
            if (! $request->filled($input)) {
                continue;
            }

            $value = $request->input($input);

            if (str_starts_with($column, 'like:')) {
                $query->where(substr($column, 5), 'like', '%'.$value.'%');

                continue;
            }

            $query->where($column, $value);
        }

        return $query;
    }

    /**
     * @param  list<string>  $allowedColumns
     */
    public static function applySort(
        Builder $query,
        Request $request,
        array $allowedColumns,
        string $defaultColumn = 'id',
        string $defaultDirection = 'desc',
    ): Builder {
        $sort = (string) $request->input('sort', $defaultColumn);
        $direction = strtolower((string) $request->input('direction', $defaultDirection));
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : $defaultDirection;

        if (! in_array($sort, $allowedColumns, true)) {
            $sort = $defaultColumn;
        }

        return $query->orderBy($sort, $direction);
    }
}
