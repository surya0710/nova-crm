<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProjectBudgetRequest;
use App\Http\Resources\ProjectBudgetResource;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ProjectBudgetController extends Controller
{
    public function __construct(protected BudgetService $budgetService) {}

    public function show(Project $project): JsonResponse
    {
        $this->authorize('viewBudgets', $project);

        $budget = ProjectBudget::query()
            ->where('project_id', $project->id)
            ->with('items')
            ->latest('id')
            ->first();

        return response()->json([
            'data' => $budget ? new ProjectBudgetResource($budget) : null,
        ]);
    }

    public function update(UpdateProjectBudgetRequest $request, Project $project): ProjectBudgetResource|JsonResponse
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
                $budget = $this->budgetService->update($budget, $validated, $items, $request->user());
            } else {
                $budget = $this->budgetService->create($project, $validated, $items ?? [], $request->user());
            }
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return new ProjectBudgetResource($budget->load('items'));
    }
}
