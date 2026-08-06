<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Services\WatcherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskWatcherController extends Controller
{
    public function __construct(protected WatcherService $watcherService) {}

    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeWatcher($request, $task);

        $user = $this->resolveWatcherUser($request);

        $this->watcherService->watchTask($task, $user, $request->user());

        return redirect()
            ->back()
            ->with('status', 'task-watcher-added');
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeWatcher($request, $task);

        $user = $this->resolveWatcherUser($request);

        $this->watcherService->unwatchTask($task, $user, $request->user());

        return redirect()
            ->back()
            ->with('status', 'task-watcher-removed');
    }

    protected function authorizeWatcher(Request $request, Task $task): void
    {
        $user = $request->user();
        abort_unless($user?->hasPermission('projects.watchers.manage', $task->organization), 403);

        if ($task->project) {
            $this->authorize('manageWatchers', $task->project);
        }
    }

    protected function resolveWatcherUser(Request $request): User
    {
        $userId = $request->integer('user_id') ?: null;

        if (! $userId || (int) $userId === (int) $request->user()->id) {
            return $request->user();
        }

        return User::query()->findOrFail($userId);
    }
}
