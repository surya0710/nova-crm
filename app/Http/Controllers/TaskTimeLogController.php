<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskTimeLogRequest;
use App\Models\Task;
use App\Models\TaskTimeLog;
use App\Services\TimeTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskTimeLogController extends Controller
{
    public function __construct(protected TimeTrackingService $timeTracking) {}

    public function index(Task $task): View
    {
        $this->authorize('view', $task);

        return view('tasks.time-logs.index', [
            'task' => $task,
            'timeLogs' => $task->timeLogs()->with('user')->latest('start_time')->get(),
        ]);
    }

    public function store(StoreTaskTimeLogRequest $request, Task $task): RedirectResponse
    {
        $this->timeTracking->logManual($task, $request->validated(), $request->user());

        return redirect()
            ->route('tasks.time-logs.index', $task)
            ->with('status', 'task-time-logged');
    }

    public function start(Task $task, Request $request): RedirectResponse
    {
        $this->authorize('timeLog', $task);

        $this->timeTracking->startTimer($task, $request->user(), $request->string('description')->toString() ?: null);

        return back()->with('status', 'task-timer-started');
    }

    public function stop(Task $task, Request $request): RedirectResponse
    {
        $this->authorize('timeLog', $task);

        $this->timeTracking->stopTimer($task, $request->user());

        return back()->with('status', 'task-timer-stopped');
    }

    public function destroy(Task $task, TaskTimeLog $timeLog, Request $request): RedirectResponse
    {
        $this->assertBelongsToTask($task, $timeLog);
        $this->authorize('delete', $timeLog);

        $timeLog->delete();

        return redirect()
            ->route('tasks.time-logs.index', $task)
            ->with('status', 'task-time-log-deleted');
    }

    protected function assertBelongsToTask(Task $task, TaskTimeLog $timeLog): void
    {
        abort_unless((int) $timeLog->task_id === (int) $task->id, 404);
    }
}
