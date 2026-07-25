<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\ProgressUpdate;
use App\Models\Project;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

class RecentlyUpdatedProjectsWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'recently_updated_projects';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'projects.progress.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $latestUpdateIds = ProgressUpdate::query()
            ->where('organization_id', $organization->id)
            ->select('project_id', DB::raw('MAX(id) as latest_id'))
            ->groupBy('project_id')
            ->orderByDesc('latest_id')
            ->limit(5)
            ->pluck('latest_id');

        $updates = ProgressUpdate::query()
            ->whereIn('id', $latestUpdateIds)
            ->with(['project:id,name,slug,project_number,completion_percentage', 'updater:id,name'])
            ->orderByDesc('created_at')
            ->get(['id', 'project_id', 'updated_by', 'progress_percentage', 'summary', 'created_at']);

        $projects = Project::query()
            ->whereIn('id', $updates->pluck('project_id'))
            ->get(['id', 'name', 'slug', 'project_number', 'completion_percentage'])
            ->keyBy('id');

        return [
            'count' => $updates->count(),
            'updates' => $updates,
            'projects' => $projects,
        ];
    }
}
