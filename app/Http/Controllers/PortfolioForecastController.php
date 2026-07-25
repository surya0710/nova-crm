<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Services\ForecastService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortfolioForecastController extends Controller
{
    public function __construct(protected ForecastService $forecastService) {}

    public function index(Request $request, TenantContext $tenant): View
    {
        abort_unless($request->user()?->hasPermission('projects.forecasts.view'), 403);

        $portfolios = Portfolio::query()
            ->where('organization_id', $tenant->id())
            ->whereNull('archived_at')
            ->withCount('projects')
            ->orderBy('name')
            ->get();

        return view('portfolios.forecasts.index', [
            'portfolios' => $portfolios,
            'organization' => $tenant->get(),
        ]);
    }

    public function show(Request $request, Portfolio $portfolio): View
    {
        abort_unless($request->user()?->hasPermission('projects.forecasts.view', $portfolio->organization), 403);

        $forecast = $this->forecastService->forPortfolio($portfolio, $request->user());

        return view('portfolios.forecasts.show', [
            'portfolio' => $portfolio,
            'forecast' => $forecast,
        ]);
    }
}
