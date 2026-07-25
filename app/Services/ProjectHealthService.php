<?php

namespace App\Services;

use App\Events\ProjectCompleted;
use App\Events\ProjectDelayed;
use App\Events\ProjectHealthChanged;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectHealthSnapshot;
use App\Models\ProjectMilestone;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class ProjectHealthService
{
    /**
     * @return array<string, int>
     */
    public function portfolioSummary(Organization $organization): array
    {
        $statuses = array_keys(config('projects.health_statuses', []));
        $counts = array_fill_keys($statuses, 0);

        $latestIds = ProjectHealthSnapshot::query()
            ->where('organization_id', $organization->id)
            ->selectRaw('MAX(id) as id')
            ->groupBy('project_id')
            ->pluck('id');

        if ($latestIds->isEmpty()) {
            return $counts;
        }

        ProjectHealthSnapshot::query()
            ->whereIn('id', $latestIds)
            ->get(['health_status'])
            ->each(function (ProjectHealthSnapshot $snapshot) use (&$counts): void {
                if (array_key_exists($snapshot->health_status, $counts)) {
                    $counts[$snapshot->health_status]++;
                }
            });

        return $counts;
    }

    public function latest(Project $project): ?ProjectHealthSnapshot
    {
        return $project->healthSnapshots()->first();
    }

    public function calculate(Project $project, ?User $actor = null): ProjectHealthSnapshot
    {
        $project->loadMissing(['status', 'progressUpdates']);

        $taskPct = $this->taskCompletionPercentage($project);
        $milestonePct = $this->milestoneCompletionPercentage($project);
        $manualPct = $this->manualCompletionPercentage($project);
        $completion = $this->calculateCompletionPercentage($project);

        $overdueTasks = $this->detectOverdueTasks($project);
        $delayedMilestones = $this->detectDelayedMilestones($project);
        $scheduleVariance = $this->scheduleVariance($project, $completion);
        $budgetVariance = $this->budgetVariance($project);
        $estimatedCompletion = $this->predictCompletionDate($project);

        $metrics = [
            'task_completion_percentage' => $taskPct,
            'milestone_completion_percentage' => $milestonePct,
            'manual_completion_percentage' => $manualPct,
            'overdue_tasks_count' => $overdueTasks->count(),
            'delayed_milestones_count' => $delayedMilestones->count(),
            'schedule_variance_days' => $scheduleVariance,
            'budget_variance' => $budgetVariance,
        ];

        $healthStatus = $this->determineHealthStatus($project, $metrics);
        $previous = $this->latest($project);
        $previousStatus = $previous?->health_status;

        $snapshot = ProjectHealthSnapshot::query()->create([
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'health_status' => $healthStatus,
            'completion_percentage' => $completion,
            'schedule_variance' => $scheduleVariance,
            'budget_variance' => $budgetVariance,
            'estimated_completion_date' => $estimatedCompletion,
            'calculated_at' => now(),
            'metadata' => [
                'metrics' => $metrics,
                'overdue_task_ids' => $overdueTasks->pluck('id')->all(),
                'delayed_milestone_ids' => $delayedMilestones->pluck('id')->all(),
            ],
        ]);

        if ($previousStatus !== null && $previousStatus !== $healthStatus) {
            $this->dispatchHealthChanged($project, $snapshot, $previousStatus, $actor);

            if ($healthStatus === 'completed' && $previousStatus !== 'completed') {
                $this->dispatchProjectCompleted($project, $snapshot, $actor);
            }

            if ($healthStatus === 'delayed' && $previousStatus !== 'delayed') {
                $this->dispatchProjectDelayed($project, $snapshot, $actor);
            }
        } elseif ($previousStatus === null && in_array($healthStatus, ['completed', 'delayed'], true)) {
            if ($healthStatus === 'completed') {
                $this->dispatchProjectCompleted($project, $snapshot, $actor);
            }

            if ($healthStatus === 'delayed') {
                $this->dispatchProjectDelayed($project, $snapshot, $actor);
            }
        }

        return $snapshot->fresh();
    }

    public function calculateCompletionPercentage(Project $project): int
    {
        $weights = config('projects.completion_weights', [
            'task' => 0.5,
            'milestone' => 0.3,
            'manual' => 0.2,
        ]);

        $taskPct = $this->taskCompletionPercentage($project);
        $milestonePct = $this->milestoneCompletionPercentage($project);
        $manualPct = $this->manualCompletionPercentage($project);

        $weighted = ($taskPct * ($weights['task'] ?? 0))
            + ($milestonePct * ($weights['milestone'] ?? 0))
            + ($manualPct * ($weights['manual'] ?? 0));

        return (int) min(100, max(0, round($weighted)));
    }

    /**
     * @return Collection<int, ProjectMilestone>
     */
    public function detectDelayedMilestones(Project $project): Collection
    {
        return $project->milestones()
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();
    }

    /**
     * @return Collection<int, Task>
     */
    public function detectOverdueTasks(Project $project): Collection
    {
        return $this->projectTasksQuery($project)
            ->with('taskStatus')
            ->get()
            ->filter(fn (Task $task) => $task->isOverdue())
            ->values();
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    public function determineHealthStatus(Project $project, array $metrics): string
    {
        if ($project->isArchived()) {
            return 'archived';
        }

        $weightedCompletion = $this->calculateCompletionPercentage($project);

        if ($this->isProjectClosed($project) || $weightedCompletion >= 100) {
            return 'completed';
        }

        $thresholds = config('projects.health_thresholds', []);
        $overdueTasks = (int) ($metrics['overdue_tasks_count'] ?? 0);
        $missedMilestones = (int) ($metrics['delayed_milestones_count'] ?? 0);
        $scheduleVariance = (float) ($metrics['schedule_variance_days'] ?? 0);

        if ($overdueTasks >= (int) ($thresholds['overdue_tasks_delayed'] ?? 3)
            || $missedMilestones >= (int) ($thresholds['missed_milestones_delayed'] ?? 2)
            || $scheduleVariance >= (float) ($thresholds['schedule_variance_delayed_days'] ?? 7)) {
            return 'delayed';
        }

        if ($overdueTasks >= (int) ($thresholds['overdue_tasks_at_risk'] ?? 1)
            || $missedMilestones >= (int) ($thresholds['missed_milestones_at_risk'] ?? 1)
            || $scheduleVariance >= (float) ($thresholds['schedule_variance_at_risk_days'] ?? 3)) {
            return 'at_risk';
        }

        return 'on_track';
    }

    public function predictCompletionDate(Project $project): ?Carbon
    {
        if ($this->isProjectClosed($project) || $this->calculateCompletionPercentage($project) >= 100) {
            return $project->actual_end_date ?? now()->toDateString();
        }

        if ($project->planned_end_date) {
            $completion = $this->calculateCompletionPercentage($project);
            $remainingPct = max(1, 100 - $completion);
            $elapsedDays = $project->start_date
                ? max(1, $project->start_date->diffInDays(now()))
                : 1;

            $completedPct = max(1, 100 - $remainingPct);
            $daysPerPercent = $elapsedDays / $completedPct;
            $daysRemaining = (int) ceil($remainingPct * $daysPerPercent);

            return now()->copy()->addDays($daysRemaining)->startOfDay();
        }

        return null;
    }

    protected function taskCompletionPercentage(Project $project): int
    {
        $tasks = $this->projectTasksQuery($project)->get(['completion_percentage']);

        if ($tasks->isEmpty()) {
            return 0;
        }

        return (int) round((float) $tasks->avg('completion_percentage'));
    }

    protected function milestoneCompletionPercentage(Project $project): int
    {
        $total = $project->milestones()->count();

        if ($total === 0) {
            return 0;
        }

        $completed = $project->milestones()->where('status', 'completed')->count();

        return (int) round(($completed / $total) * 100);
    }

    protected function manualCompletionPercentage(Project $project): int
    {
        $latestUpdate = $project->relationLoaded('progressUpdates')
            ? $project->progressUpdates->first()
            : $project->progressUpdates()->first();

        if ($latestUpdate) {
            return (int) $latestUpdate->progress_percentage;
        }

        return (int) ($project->completion_percentage ?? 0);
    }

    protected function scheduleVariance(Project $project, int $completion): ?float
    {
        if (! $project->planned_end_date) {
            return null;
        }

        if ($completion >= 100) {
            if ($project->actual_end_date) {
                return (float) $project->actual_end_date->startOfDay()
                    ->diffInDays($project->planned_end_date->startOfDay(), false);
            }

            return 0.0;
        }

        $daysFromPlan = now()->startOfDay()
            ->diffInDays($project->planned_end_date->startOfDay(), false);

        return $daysFromPlan < 0 ? (float) abs($daysFromPlan) : 0.0;
    }

    protected function budgetVariance(Project $project): ?float
    {
        if ($project->estimated_budget === null) {
            return null;
        }

        $actual = (float) ($project->actual_budget ?? 0);
        $estimated = (float) $project->estimated_budget;

        return round($actual - $estimated, 2);
    }

    protected function isProjectClosed(Project $project): bool
    {
        if ($project->relationLoaded('status') && $project->status) {
            return (bool) $project->status->is_closed;
        }

        if ($project->status_id) {
            return (bool) $project->status()->value('is_closed');
        }

        return false;
    }

    /**
     * @return Builder<Task>
     */
    protected function projectTasksQuery(Project $project): Builder
    {
        return Task::query()
            ->where('organization_id', $project->organization_id)
            ->where(function (Builder $query) use ($project): void {
                $query->where('project_id', $project->id)
                    ->orWhere(function (Builder $inner) use ($project): void {
                        $inner->where('taskable_type', $project->getMorphClass())
                            ->where('taskable_id', $project->id);
                    });
            });
    }

    protected function dispatchHealthChanged(
        Project $project,
        ProjectHealthSnapshot $snapshot,
        string $previousStatus,
        ?User $actor,
    ): void {
        $runtime = app(WorkflowRuntimeContext::class);

        event(ProjectHealthChanged::forModel(
            $project->fresh(),
            [
                'actor_id' => $actor?->id,
                'previous_health_status' => $previousStatus,
                'health_status' => $snapshot->health_status,
                'snapshot_id' => $snapshot->id,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        $this->notifyStakeholders(
            $project,
            $actor,
            __('Project health changed'),
            __(':project health status changed from :previous to :current.', [
                'project' => $project->name,
                'previous' => config('projects.health_statuses.'.$previousStatus, $previousStatus),
                'current' => config('projects.health_statuses.'.$snapshot->health_status, $snapshot->health_status),
            ]),
        );
    }

    protected function dispatchProjectCompleted(
        Project $project,
        ProjectHealthSnapshot $snapshot,
        ?User $actor,
    ): void {
        $runtime = app(WorkflowRuntimeContext::class);

        event(ProjectCompleted::forModel(
            $project->fresh(),
            [
                'actor_id' => $actor?->id,
                'snapshot_id' => $snapshot->id,
                'completion_percentage' => $snapshot->completion_percentage,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));
    }

    protected function dispatchProjectDelayed(
        Project $project,
        ProjectHealthSnapshot $snapshot,
        ?User $actor,
    ): void {
        $runtime = app(WorkflowRuntimeContext::class);

        event(ProjectDelayed::forModel(
            $project->fresh(),
            [
                'actor_id' => $actor?->id,
                'snapshot_id' => $snapshot->id,
                'schedule_variance' => $snapshot->schedule_variance,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        $this->notifyStakeholders(
            $project,
            $actor,
            __('Project delayed'),
            __(':project has been marked as delayed.', ['project' => $project->name]),
        );
    }

    protected function notifyStakeholders(Project $project, ?User $actor, string $title, string $message): void
    {
        $project->loadMissing(['owner', 'manager']);

        foreach ([$project->owner, $project->manager] as $recipient) {
            if (! $recipient || ($actor && $recipient->id === $actor->id)) {
                continue;
            }

            $recipient->notify(new CrmNotification(
                title: $title,
                message: $message,
                actionUrl: Route::has('projects.show') ? route('projects.show', $project) : null,
                organizationId: (int) $project->organization_id,
            ));
        }
    }
}
