<?php

namespace App\Services;

use App\Events\ProjectWatcherAdded;
use App\Events\ProjectWatcherRemoved;
use App\Events\TaskWatcherAdded;
use App\Events\TaskWatcherRemoved;
use App\Models\Project;
use App\Models\ProjectWatcher;
use App\Models\Task;
use App\Models\TaskWatcher;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class WatcherService
{
    public function __construct(protected ?NotificationPreferenceService $preferences = null) {}

    public function watchProject(Project $project, User $user, ?User $actor = null): ProjectWatcher
    {
        $actor ??= $user;

        $existing = ProjectWatcher::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $watcher = ProjectWatcher::query()->create([
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        $runtime = app(WorkflowRuntimeContext::class);
        event(ProjectWatcherAdded::forModel(
            $watcher,
            [
                'actor_id' => $actor->id,
                'project_id' => $project->id,
                'user_id' => $user->id,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        return $watcher->fresh();
    }

    public function unwatchProject(Project $project, User $user, ?User $actor = null): void
    {
        $actor ??= $user;

        $watcher = ProjectWatcher::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $watcher) {
            return;
        }

        $runtime = app(WorkflowRuntimeContext::class);
        event(ProjectWatcherRemoved::forModel(
            $watcher,
            [
                'actor_id' => $actor->id,
                'project_id' => $project->id,
                'user_id' => $user->id,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        $watcher->delete();
    }

    public function watchTask(Task $task, User $user, ?User $actor = null): TaskWatcher
    {
        $actor ??= $user;

        $existing = TaskWatcher::query()
            ->where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $watcher = TaskWatcher::query()->create([
            'organization_id' => $task->organization_id,
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);

        $runtime = app(WorkflowRuntimeContext::class);
        event(TaskWatcherAdded::forModel(
            $watcher,
            [
                'actor_id' => $actor->id,
                'task_id' => $task->id,
                'user_id' => $user->id,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        return $watcher->fresh();
    }

    public function unwatchTask(Task $task, User $user, ?User $actor = null): void
    {
        $actor ??= $user;

        $watcher = TaskWatcher::query()
            ->where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $watcher) {
            return;
        }

        $runtime = app(WorkflowRuntimeContext::class);
        event(TaskWatcherRemoved::forModel(
            $watcher,
            [
                'actor_id' => $actor->id,
                'task_id' => $task->id,
                'user_id' => $user->id,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        $watcher->delete();
    }

    /**
     * @return array{projects: Collection<int, Project>, tasks: Collection<int, Task>}
     */
    public function listWatching(User $user, ?int $organizationId = null): array
    {
        $organizationId ??= app(TenantContext::class)->id();

        if (! $organizationId) {
            throw ValidationException::withMessages([
                'organization' => __('An organization context is required.'),
            ]);
        }

        $projectIds = ProjectWatcher::query()
            ->where('organization_id', $organizationId)
            ->where('user_id', $user->id)
            ->pluck('project_id');

        $taskIds = TaskWatcher::query()
            ->where('organization_id', $organizationId)
            ->where('user_id', $user->id)
            ->pluck('task_id');

        return [
            'projects' => Project::query()->whereKey($projectIds)->orderBy('name')->get(),
            'tasks' => Task::query()->whereKey($taskIds)->orderBy('title')->get(),
        ];
    }

    public function notifyWatchers(
        Project|Task $subject,
        string $eventType,
        string $message,
        ?User $actor = null,
        ?string $title = null,
    ): void {
        $watchers = $this->resolveWatchers($subject);
        $organizationId = (int) $subject->organization_id;
        $title ??= __('Update');

        foreach ($watchers as $user) {
            if ($actor && (int) $user->id === (int) $actor->id) {
                continue;
            }

            if ($this->preferences()->isMuted($user, $subject)) {
                continue;
            }

            if (! $this->preferences()->shouldNotify($user, $eventType, $organizationId)) {
                continue;
            }

            $user->notify(new CrmNotification(
                title: $title,
                message: $message,
                actionUrl: $this->actionUrl($subject),
                organizationId: $organizationId,
            ));
        }
    }

    /**
     * @return Collection<int, User>
     */
    protected function resolveWatchers(Project|Task $subject): Collection
    {
        if ($subject instanceof Project) {
            $userIds = ProjectWatcher::query()
                ->where('project_id', $subject->id)
                ->pluck('user_id');
        } else {
            $userIds = TaskWatcher::query()
                ->where('task_id', $subject->id)
                ->pluck('user_id');

            if ($subject->project_id) {
                $userIds = $userIds->merge(
                    ProjectWatcher::query()
                        ->where('project_id', $subject->project_id)
                        ->pluck('user_id')
                );
            }
        }

        return User::query()->whereKey($userIds->unique()->values()->all())->get();
    }

    protected function actionUrl(Project|Task $subject): ?string
    {
        if ($subject instanceof Project) {
            return Route::has('projects.show') ? route('projects.show', $subject) : null;
        }

        return Route::has('tasks.show') ? route('tasks.show', $subject) : null;
    }

    protected function preferences(): NotificationPreferenceService
    {
        return $this->preferences ??= app(NotificationPreferenceService::class);
    }
}
