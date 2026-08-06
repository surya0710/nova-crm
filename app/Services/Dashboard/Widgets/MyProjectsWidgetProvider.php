<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\TenantContext;

class MyProjectsWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'my_projects';
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
            ->where(function ($query) use ($user) {
                $query->where('owner_id', $user->id)
                    ->orWhere('manager_id', $user->id)
                    ->orWhereHas('members', function ($memberQuery) use ($user) {
                        $memberQuery
                            ->where('user_id', $user->id)
                            ->where('is_active', true);
                    });
            });

        $projects = (clone $baseQuery)
            ->with(['status:id,name,slug,color', 'projectType:id,name'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get(['id', 'name', 'slug', 'project_number', 'status_id', 'project_type_id', 'completion_percentage', 'planned_end_date']);

        return [
            'total' => (clone $baseQuery)->count(),
            'active_count' => (clone $baseQuery)
                ->whereHas('status', fn ($statusQuery) => $statusQuery->where('is_closed', false))
                ->count(),
            'projects' => $projects,
        ];
    }
}
