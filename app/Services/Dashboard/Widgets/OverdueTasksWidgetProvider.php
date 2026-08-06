<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;

class OverdueTasksWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'overdue_tasks';
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

        $query = Task::query()
            ->where('is_archived', false)
            ->where(function (Builder $q) {
                $q->where(function (Builder $inner) {
                    $inner->whereNotNull('status_id')
                        ->whereHas('taskStatus', fn ($s) => $s->where('is_closed', false));
                })->orWhere(function (Builder $inner) {
                    $inner->whereNull('status_id')
                        ->whereIn('status', ['pending', 'in_progress']);
                });
            })
            ->where(function (Builder $q) {
                $q->where(function (Builder $inner) {
                    $inner->whereNotNull('due_date')->whereDate('due_date', '<', today());
                })->orWhere(function (Builder $inner) {
                    $inner->whereNull('due_date')->whereNotNull('due_at')->where('due_at', '<', now());
                });
            });

        $tasks = (clone $query)
            ->with(['assignee:id,name'])
            ->orderByRaw('COALESCE(due_date, DATE(due_at))')
            ->limit(5)
            ->get(['id', 'title', 'status', 'assigned_to', 'due_at', 'due_date']);

        return [
            'count' => (clone $query)->count(),
            'tasks' => $tasks,
        ];
    }
}
