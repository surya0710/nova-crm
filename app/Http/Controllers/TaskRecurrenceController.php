<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRecurrenceRequest;
use App\Http\Requests\UpdateTaskRecurrenceRequest;
use App\Models\Task;
use App\Models\TaskRecurrence;
use App\Services\TaskRecurrenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TaskRecurrenceController extends Controller
{
    public function __construct(protected TaskRecurrenceService $recurrenceService) {}

    public function store(StoreTaskRecurrenceRequest $request, Task $task): RedirectResponse
    {
        try {
            $this->recurrenceService->create($task, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->back()
            ->with('status', 'task-recurrence-created');
    }

    public function update(
        UpdateTaskRecurrenceRequest $request,
        Task $task,
        TaskRecurrence $recurrence,
    ): RedirectResponse {
        $this->assertBelongsToTask($task, $recurrence);

        try {
            $this->recurrenceService->update($recurrence, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->back()
            ->with('status', 'task-recurrence-updated');
    }

    public function destroy(Request $request, Task $task, TaskRecurrence $recurrence): RedirectResponse
    {
        $this->authorize('delete', $recurrence);
        $this->assertBelongsToTask($task, $recurrence);

        $this->recurrenceService->delete($recurrence, $request->user());

        return redirect()
            ->back()
            ->with('status', 'task-recurrence-deleted');
    }

    protected function assertBelongsToTask(Task $task, TaskRecurrence $recurrence): void
    {
        abort_unless((int) $recurrence->task_id === (int) $task->id, 404);
    }
}
