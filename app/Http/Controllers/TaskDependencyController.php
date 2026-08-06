<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskDependencyRequest;
use App\Models\Task;
use App\Models\TaskDependency;
use App\Services\TaskDependencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskDependencyController extends Controller
{
    public function __construct(protected TaskDependencyService $dependencyService) {}

    public function index(Task $task): View
    {
        $this->authorize('view', $task);

        $task->load([
            'predecessorDependencies.predecessor.assignee',
            'predecessorDependencies.predecessor.taskStatus',
            'successorDependencies.successor',
            'assignee',
            'taskStatus',
        ]);

        return view('tasks.dependencies.index', [
            'task' => $task,
            'predecessors' => $task->predecessorDependencies,
            'successors' => $task->successorDependencies,
            'blockedBy' => $this->dependencyService->blockedBySummary($task),
            'chain' => $this->dependencyService->dependencyChain($task),
            'dependencyTypes' => config('tasks.dependency_types'),
        ]);
    }

    public function store(StoreTaskDependencyRequest $request, Task $task): RedirectResponse
    {
        $validated = $request->validated();
        $predecessor = Task::query()->findOrFail($validated['predecessor_task_id']);

        $this->dependencyService->create(
            $predecessor,
            $task,
            ['dependency_type' => $validated['dependency_type'] ?? 'finish_to_start'],
            $request->user(),
        );

        return redirect()
            ->route('tasks.dependencies.index', $task)
            ->with('status', 'task-dependency-created');
    }

    public function destroy(Task $task, TaskDependency $dependency, Request $request): RedirectResponse
    {
        $this->assertBelongsToTask($task, $dependency);
        $this->authorize('delete', $dependency);

        $this->dependencyService->delete($dependency, $request->user());

        return redirect()
            ->route('tasks.dependencies.index', $task)
            ->with('status', 'task-dependency-removed');
    }

    protected function assertBelongsToTask(Task $task, TaskDependency $dependency): void
    {
        abort_unless(
            (int) $dependency->successor_task_id === (int) $task->id
            || (int) $dependency->predecessor_task_id === (int) $task->id,
            404
        );
    }
}
