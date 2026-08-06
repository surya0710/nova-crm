<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\ProjectWatcher;
use App\Models\TaskWatcher;
use App\Models\User;
use App\Services\TenantContext;

class WatchingWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'watching_projects';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'projects.watchers.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $projects = ProjectWatcher::query()
            ->where('user_id', $user->id)
            ->with(['project:id,name,slug,project_number,status_id,completion_percentage'])
            ->latest()
            ->limit(5)
            ->get();

        $tasks = TaskWatcher::query()
            ->where('user_id', $user->id)
            ->with(['task:id,title,status,due_at,due_date,project_id'])
            ->latest()
            ->limit(5)
            ->get();

        return [
            'project_count' => ProjectWatcher::query()->where('user_id', $user->id)->count(),
            'task_count' => TaskWatcher::query()->where('user_id', $user->id)->count(),
            'projects' => $projects,
            'tasks' => $tasks,
        ];
    }
}
