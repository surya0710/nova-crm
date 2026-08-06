<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Services\PortfolioStatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioStatisticsController extends Controller
{
    public function __construct(protected PortfolioStatisticsService $statisticsService) {}

    public function show(Request $request, Portfolio $portfolio): JsonResponse
    {
        $this->authorize('view', $portfolio);

        return response()->json([
            'data' => $this->statisticsService->forPortfolio(
                $portfolio,
                $request->user(),
                $request->boolean('dispatch_health_event'),
            ),
        ]);
    }
}
