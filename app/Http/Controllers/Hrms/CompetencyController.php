<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateCompetencyRequest;
use App\Http\Requests\Hrms\UpdateCompetencyRequest;
use App\Models\Competency;
use App\Models\CompetencyCategory;
use App\Services\Hrms\PerformanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompetencyController extends Controller
{
    public function __construct(protected PerformanceService $service)
    {
        $this->authorizeResource(Competency::class, 'competency');
    }

    public function index(): View
    {
        return view('hrms.performance.competencies.index', [
            'competencies' => Competency::query()->with('category')->latest()->paginate(20),
            'categories' => CompetencyCategory::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(CreateCompetencyRequest $request): RedirectResponse
    {
        $this->service->createCompetency($request->validated(), $request->user());

        return redirect()->route('hrms.performance.competencies.index')
            ->with('status', 'hrms-competency-created');
    }

    public function update(UpdateCompetencyRequest $request, Competency $competency): RedirectResponse
    {
        $this->service->updateCompetency($competency, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.competencies.index')
            ->with('status', 'hrms-competency-updated');
    }

    public function destroy(Competency $competency): RedirectResponse
    {
        $this->service->deleteCompetency($competency, request()->user());

        return redirect()->route('hrms.performance.competencies.index')
            ->with('status', 'hrms-competency-deleted');
    }
}
