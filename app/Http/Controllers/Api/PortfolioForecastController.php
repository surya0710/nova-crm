<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Services\ForecastService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioForecastController extends Controller
{
    public function __construct(protected ForecastService $forecastService) {}

    public function index(Request $request, TenantContext $tenant): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('projects.forecasts.view'), 403);

        $portfolios = Portfolio::query()
            ->where('organization_id', $tenant->id())
            ->whereNull('archived_at')
            ->withCount('projects')
            ->orderBy('name')
            ->get()
            ->map(fn (Portfolio $portfolio) => [
                'id' => $portfolio->id,
                'name' => $portfolio->name,
                'code' => $portfolio->code,
                'projects_count' => $portfolio->projects_count,
            ]);

        return response()->json(['data' => $portfolios]);
    }

    public function show(Request $request, Portfolio $portfolio): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('projects.forecasts.view', $portfolio->organization), 403);

        return response()->json([
            'data' => $this->forecastService->forPortfolio($portfolio, $request->user()),
        ]);
    }
}
