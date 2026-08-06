<?php

namespace App\Services\Recruitment\Concerns;

use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait ResolvesAnalyticsFilters
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolvePeriod(array $filters): array
    {
        $period = (string) ($filters['period'] ?? 'month');
        $now = now();

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'custom' => [
                isset($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : $now->copy()->startOfMonth(),
                isset($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : $now->copy()->endOfDay(),
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolveLeaderboardPeriod(array $filters): array
    {
        $period = (string) ($filters['leaderboard_period'] ?? $filters['period'] ?? 'monthly');
        $now = now();

        return match ($period) {
            'daily', 'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'weekly', 'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'quarterly', 'quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'yearly', 'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    /**
     * Managers without full recruitment manage see only authorized departments.
     *
     * @return list<int>|null  null = unrestricted
     */
    protected function authorizedDepartmentIds(?User $actor): ?array
    {
        if ($actor === null) {
            return null;
        }

        if ($actor->hasPermission('recruitment.manage') || $actor->hasPermission('recruitment.reports.manage')) {
            return null;
        }

        $employee = Employee::query()->where('user_id', $actor->id)->first();
        if (! $employee) {
            return null;
        }

        $ids = array_values(array_unique(array_filter([
            $employee->department_id,
        ])));

        return $ids === [] ? null : $ids;
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  list<int>|null  $departmentIds
     * @param  string  $column
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected function applyDepartmentScope(Builder $query, ?array $departmentIds, string $column = 'department_id'): Builder
    {
        if ($departmentIds !== null) {
            $query->whereIn($column, $departmentIds);
        }

        return $query;
    }

    protected function percent(float|int $part, float|int $whole): float
    {
        if ((float) $whole <= 0) {
            return 0.0;
        }

        return round(((float) $part / (float) $whole) * 100, 2);
    }

    protected function average(array $values): ?float
    {
        if ($values === []) {
            return null;
        }

        return round(array_sum($values) / count($values), 2);
    }
}
