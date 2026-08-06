<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CrmNotification;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

/**
 * Focused helpers invoked by workflow actions / listeners.
 * Keep business logic here — not inside workflow definitions.
 */
class ProjectAutomationService
{
    public function __construct(
        protected ?TaskService $tasks = null,
        protected ?WatcherService $watchers = null,
    ) {}

    /**
     * When a task completes, create a follow-up task if payload provides next-task data.
     *
     * @param  array<string, mixed>  $nextTaskData
     */
    public function createNextTaskOnCompletion(Task $completedTask, array $nextTaskData, User $actor): ?Task
    {
        if (! $completedTask->isClosed() && ($completedTask->status ?? null) !== 'completed') {
            return null;
        }

        if (empty($nextTaskData['title'])) {
            throw ValidationException::withMessages([
                'title' => __('A next task title is required.'),
            ]);
        }

        if (! $completedTask->project_id) {
            return null;
        }

        $project = $completedTask->project ?? Project::query()->find($completedTask->project_id);

        if (! $project || $project->isArchived()) {
            return null;
        }

        return $this->tasks()->createWorkManagement([
            'organization_id' => $completedTask->organization_id,
            'project_id' => $completedTask->project_id,
            'parent_task_id' => $completedTask->id,
            'milestone_id' => $nextTaskData['milestone_id'] ?? $completedTask->milestone_id,
            'title' => $nextTaskData['title'],
            'description' => $nextTaskData['description'] ?? null,
            'priority' => $nextTaskData['priority'] ?? $completedTask->priority ?? 'medium',
            'assigned_to' => $nextTaskData['assigned_to'] ?? $completedTask->assigned_to,
            'due_date' => $nextTaskData['due_date'] ?? null,
            'due_at' => $nextTaskData['due_at'] ?? null,
            'estimated_hours' => $nextTaskData['estimated_hours'] ?? null,
            'taskable_type' => $completedTask->taskable_type,
            'taskable_id' => $completedTask->taskable_id,
        ], $actor);
    }

    public function notifyManagerOnMilestoneComplete(ProjectMilestone $milestone, ?User $actor = null): void
    {
        $project = $milestone->project ?? Project::query()->find($milestone->project_id);

        if (! $project) {
            return;
        }

        $manager = $project->manager;

        if (! $manager) {
            return;
        }

        if ($actor && (int) $manager->id === (int) $actor->id) {
            return;
        }

        $manager->notify(new CrmNotification(
            title: __('Milestone completed'),
            message: __(':milestone on :project was completed.', [
                'milestone' => $milestone->name,
                'project' => $project->name,
            ]),
            actionUrl: Route::has('projects.show') ? route('projects.show', $project) : null,
            organizationId: (int) $project->organization_id,
        ));

        $this->watchers()->notifyWatchers(
            $project,
            'project.milestone.completed',
            __(':milestone on :project was completed.', [
                'milestone' => $milestone->name,
                'project' => $project->name,
            ]),
            $actor,
            __('Milestone completed'),
        );
    }

    public function escalateOverdueTask(Task $task, ?User $actor = null): void
    {
        if (! $task->isOverdue()) {
            return;
        }

        $recipients = collect([
            $task->assignee,
            $task->project?->manager,
            $task->project?->owner,
        ])->filter()->unique('id');

        foreach ($recipients as $recipient) {
            if ($actor && (int) $recipient->id === (int) $actor->id) {
                continue;
            }

            $recipient->notify(new CrmNotification(
                title: __('Overdue task escalation'),
                message: __(':task is overdue and needs attention.', ['task' => $task->title]),
                actionUrl: Route::has('tasks.show') ? route('tasks.show', $task) : null,
                organizationId: (int) $task->organization_id,
            ));
        }

        if ($task->project) {
            $this->watchers()->notifyWatchers(
                $task,
                'task.overdue',
                __(':task is overdue and needs attention.', ['task' => $task->title]),
                $actor,
                __('Overdue task escalation'),
            );
        }
    }

    public function notifyPmOnProjectDelayed(Project $project, ?User $actor = null, ?string $reason = null): void
    {
        $pm = $project->manager ?? $project->owner;

        if (! $pm) {
            return;
        }

        if ($actor && (int) $pm->id === (int) $actor->id) {
            // Still notify owner if manager is the actor and they differ.
            $pm = $project->owner && (int) $project->owner->id !== (int) $actor->id
                ? $project->owner
                : null;
        }

        if ($pm) {
            $pm->notify(new CrmNotification(
                title: __('Project delayed'),
                message: $reason
                    ?: __(':project appears delayed.', ['project' => $project->name]),
                actionUrl: Route::has('projects.show') ? route('projects.show', $project) : null,
                organizationId: (int) $project->organization_id,
            ));
        }

        $this->watchers()->notifyWatchers(
            $project,
            'project.delayed',
            $reason ?: __(':project appears delayed.', ['project' => $project->name]),
            $actor,
            __('Project delayed'),
        );
    }

    protected function tasks(): TaskService
    {
        return $this->tasks ??= app(TaskService::class);
    }

    protected function watchers(): WatcherService
    {
        return $this->watchers ??= app(WatcherService::class);
    }
}
