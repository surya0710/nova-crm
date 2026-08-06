<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use App\Services\TenantContext;

class RecentlyUpdatedTasksWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'recently_updated_tasks';
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

        $tasks = Task::query()
            ->where('is_archived', false)
            ->with(['assignee:id,name', 'taskStatus:id,name,color'])
            ->latest('updated_at')
            ->limit(5)
            ->get(['id', 'title', 'status', 'status_id', 'assigned_to', 'updated_at']);

        return [
            'count' => $tasks->count(),
            'tasks' => $tasks,
        ];
    }
}
