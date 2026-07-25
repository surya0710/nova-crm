<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateGoalTemplateRequest;
use App\Http\Requests\Hrms\UpdateGoalTemplateRequest;
use App\Models\GoalCategory;
use App\Models\GoalTemplate;
use App\Services\Hrms\GoalManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GoalLibraryController extends Controller
{
    public function __construct(protected GoalManagementService $service)
    {
        $this->authorizeResource(GoalTemplate::class, 'library');
    }

    public function index(): View
    {
        return view('hrms.performance.goals.library', [
            'templates' => GoalTemplate::query()->with('category')->latest()->paginate(20),
            'categories' => GoalCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'goalTypes' => config('hrms.goal_types', []),
            'measurementTypes' => config('hrms.goal_measurement_types', []),
        ]);
    }

    public function store(CreateGoalTemplateRequest $request): RedirectResponse
    {
        $this->service->createTemplate($request->validated(), $request->user());

        return redirect()->route('hrms.performance.goals.library.index')
            ->with('status', 'hrms-goal-template-created');
    }

    public function update(UpdateGoalTemplateRequest $request, GoalTemplate $library): RedirectResponse
    {
        $this->service->updateTemplate($library, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.goals.library.index')
            ->with('status', 'hrms-goal-template-updated');
    }

    public function destroy(GoalTemplate $library): RedirectResponse
    {
        $this->service->deleteTemplate($library, request()->user());

        return redirect()->route('hrms.performance.goals.library.index')
            ->with('status', 'hrms-goal-template-deleted');
    }
}
