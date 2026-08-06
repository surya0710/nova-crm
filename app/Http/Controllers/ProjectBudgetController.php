<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProjectBudgetRequest;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Services\BudgetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectBudgetController extends Controller
{
    public function __construct(protected BudgetService $budgetService) {}

    public function show(Project $project): View
    {
        $this->authorize('viewBudgets', $project);

        $budget = ProjectBudget::query()
            ->where('project_id', $project->id)
            ->with('items')
            ->latest('id')
            ->first();

        return view('projects.budgets.show', [
            'project' => $project,
            'budget' => $budget,
        ]);
    }

    public function update(UpdateProjectBudgetRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('manageBudgets', $project);

        $validated = $request->validated();
        $items = $validated['items'] ?? null;
        unset($validated['items']);

        $budget = ProjectBudget::query()
            ->where('project_id', $project->id)
            ->latest('id')
            ->first();

        try {
            if ($budget) {
                $this->budgetService->update($budget, $validated, $items, $request->user());
            } else {
                $this->budgetService->create($project, $validated, $items ?? [], $request->user());
            }
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('projects.budgets.show', $project)
            ->with('status', 'project-budget-updated');
    }
}
