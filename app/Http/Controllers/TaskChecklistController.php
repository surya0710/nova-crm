<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskChecklistRequest;
use App\Http\Requests\UpdateTaskChecklistRequest;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Services\ChecklistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskChecklistController extends Controller
{
    public function __construct(protected ChecklistService $checklistService) {}

    public function index(Task $task): View
    {
        $this->authorize('view', $task);

        return view('tasks.checklists.index', [
            'task' => $task,
            'checklists' => $task->checklists()->orderBy('sequence')->get(),
        ]);
    }

    public function store(StoreTaskChecklistRequest $request, Task $task): RedirectResponse
    {
        $this->checklistService->create($task, $request->validated(), $request->user());

        return redirect()
            ->route('tasks.checklists.index', $task)
            ->with('status', 'task-checklist-created');
    }

    public function update(UpdateTaskChecklistRequest $request, Task $task, TaskChecklist $checklist): RedirectResponse
    {
        $this->assertBelongsToTask($task, $checklist);

        $validated = $request->validated();

        if (array_key_exists('is_completed', $validated)) {
            $this->checklistService->complete($checklist, $request->user(), (bool) $validated['is_completed']);
            unset($validated['is_completed']);
        }

        if ($validated !== []) {
            $this->checklistService->update($checklist, $validated, $request->user());
        }

        return redirect()
            ->route('tasks.checklists.index', $task)
            ->with('status', 'task-checklist-updated');
    }

    public function destroy(Task $task, TaskChecklist $checklist, Request $request): RedirectResponse
    {
        $this->assertBelongsToTask($task, $checklist);
        $this->authorize('delete', $checklist);

        $this->checklistService->delete($checklist, $request->user());

        return redirect()
            ->route('tasks.checklists.index', $task)
            ->with('status', 'task-checklist-deleted');
    }

    public function complete(Task $task, TaskChecklist $checklist, Request $request): RedirectResponse
    {
        $this->assertBelongsToTask($task, $checklist);
        $this->authorize('manageChecklists', $task);

        $this->checklistService->complete($checklist, $request->user(), true);

        return back()->with('status', 'task-checklist-completed');
    }

    protected function assertBelongsToTask(Task $task, TaskChecklist $checklist): void
    {
        abort_unless((int) $checklist->task_id === (int) $task->id, 404);
    }
}
