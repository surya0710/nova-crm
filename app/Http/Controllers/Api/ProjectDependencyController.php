<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectDependencyRequest;
use App\Http\Requests\UpdateProjectDependencyRequest;
use App\Http\Resources\ProjectDependencyResource;
use App\Models\Portfolio;
use App\Models\Project;
use App\Models\ProjectDependency;
use App\Services\DependencyGraphService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ProjectDependencyController extends Controller
{
    public function __construct(protected DependencyGraphService $dependencyService) {}

    public function index(Request $request, TenantContext $tenant): JsonResponse
    {
        $this->authorize('viewAny', ProjectDependency::class);

        $portfolio = null;
        if ($portfolioId = $request->integer('portfolio_id')) {
            $portfolio = Portfolio::query()
                ->where('organization_id', $tenant->id())
                ->findOrFail($portfolioId);
        }

        $dependencies = ProjectDependency::query()
            ->where('organization_id', $tenant->id())
            ->with(['predecessor', 'successor'])
            ->latest('id')
            ->paginate($request->integer('per_page', 25));

        return response()->json([
            'data' => [
                'graph' => $this->dependencyService->graph($tenant->id(), $portfolio),
                'dependencies' => ProjectDependencyResource::collection($dependencies)->resolve(),
                'meta' => [
                    'current_page' => $dependencies->currentPage(),
                    'last_page' => $dependencies->lastPage(),
                    'per_page' => $dependencies->perPage(),
                    'total' => $dependencies->total(),
                ],
            ],
        ]);
    }

    public function store(StoreProjectDependencyRequest $request, TenantContext $tenant): JsonResponse
    {
        try {
            $dependency = $this->dependencyService->create([
                ...$request->validated(),
                'organization_id' => $tenant->id(),
            ], $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return (new ProjectDependencyResource($dependency->load(['predecessor', 'successor'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProjectDependencyRequest $request, ProjectDependency $dependency): ProjectDependencyResource|JsonResponse
    {
        try {
            $dependency = $this->dependencyService->update($dependency, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return new ProjectDependencyResource($dependency->load(['predecessor', 'successor']));
    }

    public function destroy(Request $request, ProjectDependency $dependency): JsonResponse
    {
        $this->authorize('delete', $dependency);

        $this->dependencyService->delete($dependency, $request->user());

        return response()->json(['success' => true]);
    }

    public function projectIndex(Project $project): JsonResponse
    {
        $this->authorize('viewDependencies', $project);

        return response()->json([
            'data' => $this->dependencyService->impactAnalysis($project),
        ]);
    }
}
