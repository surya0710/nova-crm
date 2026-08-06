<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;

class MyTasksWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'my_tasks';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'tasks.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $openQuery = Task::query()
            ->where('assigned_to', $user->id)
            ->where('is_archived', false)
            ->where(fn (Builder $q) => $this->applyOpenConstraint($q));

        $tasks = (clone $openQuery)
            ->orderByRaw('COALESCE(due_date, DATE(due_at)) IS NULL')
            ->orderByRaw('COALESCE(due_date, DATE(due_at))')
            ->limit(5)
            ->get(['id', 'title', 'status', 'status_id', 'due_at', 'due_date']);

        return [
            'open_count' => (clone $openQuery)->count(),
            'overdue_count' => (clone $openQuery)
                ->where(function (Builder $q) {
                    $q->where(function (Builder $inner) {
                        $inner->whereNotNull('due_date')->whereDate('due_date', '<', today());
                    })->orWhere(function (Builder $inner) {
                        $inner->whereNull('due_date')->whereNotNull('due_at')->where('due_at', '<', now());
                    });
                })
                ->count(),
            'tasks' => $tasks,
        ];
    }

    protected function applyOpenConstraint(Builder $query): void
    {
        $query->where(function (Builder $q) {
            $q->where(function (Builder $inner) {
                $inner->whereNotNull('status_id')
                    ->whereHas('taskStatus', fn ($s) => $s->where('is_closed', false));
            })->orWhere(function (Builder $inner) {
                $inner->whereNull('status_id')
                    ->whereIn('status', ['pending', 'in_progress']);
            });
        });
    }
}
