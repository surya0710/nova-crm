<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachProjectToPortfolioRequest;
use App\Http\Requests\StorePortfolioRequest;
use App\Http\Requests\UpdatePortfolioRequest;
use App\Http\Resources\PortfolioResource;
use App\Models\Portfolio;
use App\Models\Project;
use App\Services\PortfolioService;
use App\Services\PortfolioStatisticsService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class PortfolioController extends Controller
{
    public function __construct(
        protected PortfolioService $portfolioService,
        protected PortfolioStatisticsService $statisticsService,
    ) {}

    public function index(Request $request, TenantContext $tenant): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Portfolio::class);

        $portfolios = $this->portfolioService->list($tenant->id(), [
            'search' => $request->string('search')->trim()->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'owner_id' => $request->integer('owner_id') ?: null,
            'archived' => $request->has('archived') ? $request->boolean('archived') : false,
        ]);

        return PortfolioResource::collection($portfolios);
    }

    public function store(StorePortfolioRequest $request, TenantContext $tenant): JsonResponse
    {
        $portfolio = $this->portfolioService->create([
            ...$request->validated(),
            'organization_id' => $tenant->id(),
        ], $request->user());

        return (new PortfolioResource($portfolio->load(['owner', 'projects'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Portfolio $portfolio): PortfolioResource
    {
        $this->authorize('view', $portfolio);

        $portfolio->load(['owner', 'projects.status', 'programs']);

        return new PortfolioResource($portfolio);
    }

    public function update(UpdatePortfolioRequest $request, Portfolio $portfolio): PortfolioResource|JsonResponse
    {
        try {
            $portfolio = $this->portfolioService->update($portfolio, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return new PortfolioResource($portfolio->load(['owner', 'projects']));
    }

    public function destroy(Request $request, Portfolio $portfolio): JsonResponse
    {
        $this->authorize('delete', $portfolio);

        try {
            $this->portfolioService->delete($portfolio, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    public function dashboard(Portfolio $portfolio): JsonResponse
    {
        $this->authorize('viewDashboard', $portfolio);

        $portfolio->load(['owner', 'projects.status', 'programs']);

        return response()->json([
            'data' => [
                'portfolio' => new PortfolioResource($portfolio),
                'statistics' => $this->statisticsService->forPortfolio($portfolio, null, true),
            ],
        ]);
    }

    public function attachProject(AttachProjectToPortfolioRequest $request, Portfolio $portfolio): PortfolioResource|JsonResponse
    {
        $project = Project::query()->findOrFail($request->validated('project_id'));

        try {
            $portfolio = $this->portfolioService->attachProject($portfolio, $project, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return new PortfolioResource($portfolio);
    }

    public function detachProject(Request $request, Portfolio $portfolio, Project $project): PortfolioResource
    {
        $this->authorize('attachProject', $portfolio);

        $portfolio = $this->portfolioService->detachProject($portfolio, $project, $request->user());

        return new PortfolioResource($portfolio);
    }

    public function archive(Request $request, Portfolio $portfolio): PortfolioResource|JsonResponse
    {
        $this->authorize('archive', $portfolio);

        try {
            $portfolio = $this->portfolioService->archive($portfolio, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return new PortfolioResource($portfolio->load(['owner', 'projects']));
    }
}
