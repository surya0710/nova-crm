<?php

namespace App\Http\Controllers;

use App\Http\Requests\WatchProjectRequest;
use App\Models\Project;
use App\Models\User;
use App\Services\TenantContext;
use App\Services\WatcherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectWatcherController extends Controller
{
    public function __construct(protected WatcherService $watcherService) {}

    public function index(Request $request, TenantContext $tenant): View
    {
        abort_unless($request->user()?->hasPermission('projects.watchers.view'), 403);

        $watching = $this->watcherService->listWatching($request->user(), $tenant->id());

        return view('projects.watching.index', [
            'projects' => $watching['projects'],
            'tasks' => $watching['tasks'],
        ]);
    }

    public function store(WatchProjectRequest $request, Project $project): RedirectResponse
    {
        $user = $this->resolveWatcherUser($request, $project);

        $this->watcherService->watchProject($project, $user, $request->user());

        return redirect()
            ->back()
            ->with('status', 'project-watcher-added');
    }

    public function destroy(WatchProjectRequest $request, Project $project): RedirectResponse
    {
        $user = $this->resolveWatcherUser($request, $project);

        $this->watcherService->unwatchProject($project, $user, $request->user());

        return redirect()
            ->back()
            ->with('status', 'project-watcher-removed');
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
