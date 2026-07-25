<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectHealthSnapshot;
use App\Services\ProjectHealthService;
use App\Services\TenantContext;
use Illuminate\View\View;

class ProjectExecutiveDashboardController extends Controller
{
    public function __construct(protected ProjectHealthService $healthService) {}

    public function index(TenantContext $tenant): View
    {
        $this->authorize('viewAny', Project::class);

        $organization = $tenant->get();
        $portfolioHealth = $this->healthService->portfolioSummary($organization);

        $latestIds = ProjectHealthSnapshot::query()
            ->where('organization_id', $organization->id)
            ->selectRaw('MAX(id) as id')
            ->groupBy('project_id')
            ->pluck('id');

        $snapshots = $latestIds->isEmpty()
            ? collect()
            : ProjectHealthSnapshot::query()
                ->whereIn('id', $latestIds)
                ->with(['project.status', 'project.manager', 'project.owner'])
                ->get()
                ->sortBy(fn (ProjectHealthSnapshot $s) => $s->project?->name);

        $activeProjects = Project::query()
            ->where('is_archived', false)
            ->whereHas('status', fn ($q) => $q->where('is_closed', false))
            ->with(['status', 'manager'])
            ->count();

        $atRiskCount = ($portfolioHealth['at_risk'] ?? 0) + ($portfolioHealth['delayed'] ?? 0);

        return view('projects.executive.index', [
            'portfolioHealth' => $portfolioHealth,
            'snapshots' => $snapshots,
            'activeProjects' => $activeProjects,
            'atRiskCount' => $atRiskCount,
            'healthStatuses' => config('projects.health_statuses', []),
        ]);
    }
}
