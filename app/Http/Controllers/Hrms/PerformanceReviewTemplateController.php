<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreatePerformanceReviewTemplateRequest;
use App\Http\Requests\Hrms\UpdatePerformanceReviewTemplateRequest;
use App\Models\Competency;
use App\Models\PerformanceReviewTemplate;
use App\Services\Hrms\PerformanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PerformanceReviewTemplateController extends Controller
{
    public function __construct(protected PerformanceService $service)
    {
        $this->authorizeResource(PerformanceReviewTemplate::class, 'template');
    }

    public function index(): View
    {
        return view('hrms.performance.templates.index', [
            'templates' => PerformanceReviewTemplate::query()
                ->withCount(['sections', 'templateCompetencies'])
                ->latest()
                ->paginate(20),
            'competencies' => Competency::query()->where('is_active', true)->with('category')->orderBy('name')->get(),
        ]);
    }

    public function store(CreatePerformanceReviewTemplateRequest $request): RedirectResponse
    {
        $this->service->createTemplate($request->validated(), $request->user());

        return redirect()->route('hrms.performance.templates.index')
            ->with('status', 'hrms-performance-template-created');
    }

    public function update(UpdatePerformanceReviewTemplateRequest $request, PerformanceReviewTemplate $template): RedirectResponse
    {
        $this->service->updateTemplate($template, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.templates.index')
            ->with('status', 'hrms-performance-template-updated');
    }

    public function destroy(PerformanceReviewTemplate $template): RedirectResponse
    {
        $this->service->deleteTemplate($template, request()->user());

        return redirect()->route('hrms.performance.templates.index')
            ->with('status', 'hrms-performance-template-deleted');
    }
}
