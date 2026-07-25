<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WatchProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ProjectWatcherResource;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\User;
use App\Services\TenantContext;
use App\Services\WatcherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectWatcherController extends Controller
{
    public function __construct(protected WatcherService $watcherService) {}

    public function index(Request $request, TenantContext $tenant): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('projects.watchers.view'), 403);

        $watching = $this->watcherService->listWatching($request->user(), $tenant->id());

        return response()->json([
            'data' => [
                'projects' => ProjectResource::collection($watching['projects']),
                'tasks' => TaskResource::collection($watching['tasks']),
            ],
        ]);
    }

    public function store(WatchProjectRequest $request, Project $project): JsonResponse
    {
        $user = $this->resolveWatcherUser($request, $project);
        $watcher = $this->watcherService->watchProject($project, $user, $request->user());

        return (new ProjectWatcherResource($watcher->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(WatchProjectRequest $request, Project $project): JsonResponse
    {
        $user = $this->resolveWatcherUser($request, $project);
        $this->watcherService->unwatchProject($project, $user, $request->user());

        return response()->json(['success' => true]);
    }

    protected function resolveWatcherUser(Request $request, Project $project): User
    {
        $userId = $request->integer('user_id') ?: null;

        if (! $userId || (int) $userId === (int) $request->user()->id) {
            return $request->user();
        }

        abort_unless($request->user()->can('manageWatchers', $project), 403);

        return User::query()->findOrFail($userId);
    }
}
