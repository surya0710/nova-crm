<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\TenantContext;

class ActiveProjectsWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'active_projects';
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

        $baseQuery = Project::query()
            ->where('is_archived', false)
            ->whereHas('status', fn ($statusQuery) => $statusQuery->where('slug', 'active'));

        $projects = (clone $baseQuery)
            ->with(['owner:id,name', 'manager:id,name', 'status:id,name,slug,color'])
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'slug', 'project_number', 'owner_id', 'manager_id', 'status_id', 'completion_percentage']);

        return [
            'count' => (clone $baseQuery)->count(),
            'projects' => $projects,
        ];
    }
}
