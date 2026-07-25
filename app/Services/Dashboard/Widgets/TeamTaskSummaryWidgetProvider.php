<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskStatisticsService;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;

class TeamTaskSummaryWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'team_task_summary';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'tasks.manage';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $stats = app(TaskStatisticsService::class)->forOrganization($organization);

        $byAssignee = Task::query()
            ->where('is_archived', false)
            ->whereNotNull('assigned_to')
            ->where(function (Builder $q) {
                $q->where(function (Builder $inner) {
                    $inner->whereNotNull('status_id')
                        ->whereHas('taskStatus', fn ($s) => $s->where('is_closed', false));
                })->orWhere(function (Builder $inner) {
                    $inner->whereNull('status_id')
                        ->whereIn('status', ['pending', 'in_progress']);
                });
            })
            ->selectRaw('assigned_to, COUNT(*) as open_count')
            ->groupBy('assigned_to')
            ->orderByDesc('open_count')
            ->limit(8)
            ->get()
            ->map(function ($row) {
                $assignee = User::query()->find($row->assigned_to);

                return [
                    'user_id' => (int) $row->assigned_to,
                    'name' => $assignee?->name,
                    'open_count' => (int) $row->open_count,
                ];
            });

        return [
            'total' => $stats['total'],
            'open' => $stats['open'],
            'overdue' => $stats['overdue'],
            'closed' => $stats['closed'],
            'by_assignee' => $byAssignee,
        ];
    }
}
