<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachProjectToPortfolioRequest;
use App\Http\Requests\StorePortfolioRequest;
use App\Http\Requests\UpdatePortfolioRequest;
use App\Models\Portfolio;
use App\Models\Project;
use App\Services\PortfolioService;
use App\Services\PortfolioStatisticsService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function __construct(
        protected PortfolioService $portfolioService,
        protected PortfolioStatisticsService $statisticsService,
    ) {
        $this->authorizeResource(Portfolio::class, 'portfolio');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $portfolios = $this->portfolioService->list($tenant->id(), [
            'search' => $request->string('search')->trim()->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'owner_id' => $request->integer('owner_id') ?: null,
            'archived' => $request->has('archived') ? $request->boolean('archived') : false,
        ]);

        return view('portfolios.index', [
            'portfolios' => $portfolios,
            'organization' => $tenant->get(),
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('portfolios.create', [
            'portfolio' => new Portfolio(['color' => '#4f46e5', 'status' => 'active']),
            'organization' => $tenant->get(),
        ]);
    }

    public function store(StorePortfolioRequest $request, TenantContext $tenant): RedirectResponse
    {
        $portfolio = $this->portfolioService->create([
            ...$request->validated(),
            'organization_id' => $tenant->id(),
        ], $request->user());

        return redirect()
            ->route('portfolios.show', $portfolio)
            ->with('status', 'portfolio-created');
    }

    public function show(Portfolio $portfolio): View
    {
        $portfolio->load(['owner', 'projects.status', 'programs']);

        return view('portfolios.show', [
            'portfolio' => $portfolio,
            'statistics' => $this->statisticsService->forPortfolio($portfolio),
        ]);
    }

    public function edit(Portfolio $portfolio): View
    {
        $portfolio->load('projects');

        return view('portfolios.edit', [
            'portfolio' => $portfolio,
        ]);
    }

    public function update(UpdatePortfolioRequest $request, Portfolio $portfolio): RedirectResponse
    {
        try {
            $this->portfolioService->update($portfolio, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()
            ->route('portfolios.show', $portfolio)
            ->with('status', 'portfolio-updated');
    }

    public function destroy(Portfolio $portfolio, Request $request): RedirectResponse
    {
        try {
            $this->portfolioService->delete($portfolio, $request->user());
        } catch (ValidationException $e) {
            return redirect()
                ->route('portfolios.index')
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('portfolios.index')
            ->with('status', 'portfolio-deleted');
    }

    public function dashboard(Portfolio $portfolio): View
    {
        $this->authorize('viewDashboard', $portfolio);

        $portfolio->load(['owner', 'projects.status', 'programs', 'risks', 'issues']);

        return view('portfolios.dashboard', [
            'portfolio' => $portfolio,
            'statistics' => $this->statisticsService->forPortfolio($portfolio, null, true),
        ]);
    }

    public function attachProject(AttachProjectToPortfolioRequest $request, Portfolio $portfolio): RedirectResponse
    {
        $project = Project::query()->findOrFail($request->validated('project_id'));

        try {
            $this->portfolioService->attachProject($portfolio, $project, $request->user());
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        return redirect()
            ->back()
            ->with('status', 'portfolio-project-attached');
    }

    public function detachProject(Portfolio $portfolio, Project $project, Request $request): RedirectResponse
    {
        $this->authorize('attachProject', $portfolio);

        $this->portfolioService->detachProject($portfolio, $project, $request->user());

        return redirect()
            ->back()
            ->with('status', 'portfolio-project-detached');
    }

    public function archive(Portfolio $portfolio, Request $request): RedirectResponse
    {
        $this->authorize('archive', $portfolio);

        try {
            $this->portfolioService->archive($portfolio, $request->user());
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        return redirect()
            ->route('portfolios.show', $portfolio)
            ->with('status', 'portfolio-archived');
    }
}
