<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\ProjectMilestone;
use App\Models\User;
use App\Services\TenantContext;

class ProjectMilestonesWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'project_milestones';
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

        $baseQuery = ProjectMilestone::query()
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_date')
            ->where('due_date', '>=', now()->startOfDay())
            ->whereHas('project', fn ($projectQuery) => $projectQuery->where('is_archived', false));

        $milestones = (clone $baseQuery)
            ->with(['project:id,name,slug,project_number'])
            ->orderBy('due_date')
            ->limit(5)
            ->get(['id', 'project_id', 'name', 'due_date', 'status', 'sequence']);

        return [
            'count' => (clone $baseQuery)->count(),
            'milestones' => $milestones,
        ];
    }
}
