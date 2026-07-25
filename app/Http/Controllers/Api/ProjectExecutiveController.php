<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectHealthSnapshotResource;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\ProjectHealthSnapshot;
use App\Services\ProjectHealthService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectExecutiveController extends Controller
{
    public function __construct(protected ProjectHealthService $healthService) {}

    public function summary(Request $request, TenantContext $tenant): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        $organization = $tenant->get();
        $portfolioCounts = $this->healthService->portfolioSummary($organization);

        $latestIds = ProjectHealthSnapshot::query()
            ->where('organization_id', $organization->id)
            ->selectRaw('MAX(id) as id')
            ->groupBy('project_id')
            ->pluck('id');

        $snapshots = $latestIds->isEmpty()
            ? collect()
            : ProjectHealthSnapshot::query()
                ->whereIn('id', $latestIds)
                ->with(['project.status', 'project.manager'])
                ->get();

        return response()->json([
            'data' => [
                'portfolio_health' => $portfolioCounts,
                'projects' => ProjectHealthSnapshotResource::collection($snapshots),
                'active_projects' => ProjectResource::collection(
                    Project::query()
                        ->where('is_archived', false)
                        ->whereHas('status', fn ($q) => $q->where('is_closed', false))
                        ->with(['status', 'manager', 'healthSnapshots'])
                        ->latest()
                        ->limit($request->integer('limit', 20))
                        ->get()
                ),
            ],
        ]);
    }
}
