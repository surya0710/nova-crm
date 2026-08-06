<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateGoalCategoryRequest;
use App\Http\Requests\Hrms\UpdateGoalCategoryRequest;
use App\Models\GoalCategory;
use App\Services\Hrms\GoalManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GoalCategoryController extends Controller
{
    public function __construct(protected GoalManagementService $service)
    {
        $this->authorizeResource(GoalCategory::class, 'goal_category');
    }

    public function index(): View
    {
        return view('hrms.performance.goal-categories.index', [
            'categories' => GoalCategory::query()->latest()->paginate(20),
        ]);
    }

    public function store(CreateGoalCategoryRequest $request): RedirectResponse
    {
        $this->service->createCategory($request->validated(), $request->user());

        return redirect()->route('hrms.performance.goal-categories.index')
            ->with('status', 'hrms-goal-category-created');
    }

    public function update(UpdateGoalCategoryRequest $request, GoalCategory $goalCategory): RedirectResponse
    {
        $this->service->updateCategory($goalCategory, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.goal-categories.index')
            ->with('status', 'hrms-goal-category-updated');
    }

    public function destroy(GoalCategory $goalCategory): RedirectResponse
    {
        $this->service->deleteCategory($goalCategory, request()->user());

        return redirect()->route('hrms.performance.goal-categories.index')
            ->with('status', 'hrms-goal-category-deleted');
    }
}
