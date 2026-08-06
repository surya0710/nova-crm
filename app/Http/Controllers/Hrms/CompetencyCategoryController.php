<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateCompetencyCategoryRequest;
use App\Http\Requests\Hrms\UpdateCompetencyCategoryRequest;
use App\Models\CompetencyCategory;
use App\Services\Hrms\PerformanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompetencyCategoryController extends Controller
{
    public function __construct(protected PerformanceService $service)
    {
        $this->authorizeResource(CompetencyCategory::class, 'category');
    }

    public function index(): View
    {
        return view('hrms.performance.categories.index', [
            'categories' => CompetencyCategory::query()->withCount('competencies')->latest()->paginate(20),
        ]);
    }

    public function store(CreateCompetencyCategoryRequest $request): RedirectResponse
    {
        $this->service->createCompetencyCategory($request->validated(), $request->user());

        return redirect()->route('hrms.performance.categories.index')
            ->with('status', 'hrms-competency-category-created');
    }

    public function update(UpdateCompetencyCategoryRequest $request, CompetencyCategory $category): RedirectResponse
    {
        $this->service->updateCompetencyCategory($category, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.categories.index')
            ->with('status', 'hrms-competency-category-updated');
    }

    public function destroy(CompetencyCategory $category): RedirectResponse
    {
        $this->service->deleteCompetencyCategory($category, request()->user());

        return redirect()->route('hrms.performance.categories.index')
            ->with('status', 'hrms-competency-category-deleted');
    }
}
