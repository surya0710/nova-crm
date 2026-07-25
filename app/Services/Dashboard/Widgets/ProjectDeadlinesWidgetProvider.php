<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\TenantContext;

class ProjectDeadlinesWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'project_deadlines';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'projects.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $windowStart = now()->startOfDay();
        $windowEnd = now()->addDays(30)->endOfDay();

        $baseQuery = Project::query()
            ->where('is_archived', false)
            ->whereNotNull('planned_end_date')
            ->whereBetween('planned_end_date', [$windowStart, $windowEnd]);

        $projects = (clone $baseQuery)
            ->with(['status:id,name,slug,color', 'owner:id,name'])
            ->orderBy('planned_end_date')
            ->limit(5)
            ->get(['id', 'name', 'slug', 'project_number', 'planned_end_date', 'status_id', 'owner_id', 'completion_percentage']);

        return [
            'count' => (clone $baseQuery)->count(),
            'overdue_count' => Project::query()
                ->where('is_archived', false)
                ->whereNotNull('planned_end_date')
                ->where('planned_end_date', '<', $windowStart)
                ->count(),
            'projects' => $projects,
        ];
    }
}
