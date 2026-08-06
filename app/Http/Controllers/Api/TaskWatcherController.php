<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskWatcherResource;
use App\Models\Task;
use App\Models\User;
use App\Services\WatcherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskWatcherController extends Controller
{
    public function __construct(protected WatcherService $watcherService) {}

    public function store(Request $request, Task $task): JsonResponse
    {
        $this->authorizeWatcher($request, $task);

        $user = $this->resolveWatcherUser($request);
        $watcher = $this->watcherService->watchTask($task, $user, $request->user());

        return (new TaskWatcherResource($watcher->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->authorizeWatcher($request, $task);

        $user = $this->resolveWatcherUser($request);
        $this->watcherService->unwatchTask($task, $user, $request->user());

        return response()->json(['success' => true]);
    }

    protected function authorizeWatcher(Request $request, Task $task): void
    {
        abort_unless($request->user()?->hasPermission('projects.watchers.manage', $task->organization), 403);

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
