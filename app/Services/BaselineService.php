<?php

namespace App\Services;

use App\Events\ProjectBaselineCreated;
use App\Models\Project;
use App\Models\ProjectBaseline;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class BaselineService
{
    public function __construct(
        protected ?ProjectHealthService $health = null,
    ) {}

    public function capture(Project $project, User $actor, ?string $notes = null, ?string $name = null): ProjectBaseline
    {
        return DB::transaction(function () use ($project, $actor, $notes, $name) {
            $project->loadMissing(['milestones']);

            $nextVersion = ((int) ProjectBaseline::query()
                ->where('project_id', $project->id)
                ->max('version')) + 1;

            $completion = $this->health()
                ? $this->health()->calculateCompletionPercentage($project)
                : (int) ($project->completion_percentage ?? 0);

            $taskTitles = Task::query()
                ->where('organization_id', $project->organization_id)
                ->where(function (Builder $query) use ($project): void {
                    $query->where('project_id', $project->id)
                        ->orWhere(function (Builder $inner) use ($project): void {
                            $inner->where('taskable_type', $project->getMorphClass())
                                ->where('taskable_id', $project->id);
                        });
                })
                ->orderBy('id')
                ->pluck('title')
                ->all();

            $baseline = ProjectBaseline::query()->create([
                'organization_id' => $project->organization_id,
                'project_id' => $project->id,
                'version' => $nextVersion,
                'name' => $name ?? __('Baseline v:version', ['version' => $nextVersion]),
                'scope_snapshot' => [
                    'milestones' => $project->milestones->map(fn ($m) => [
                        'id' => $m->id,
                        'title' => $m->name,
                        'status' => $m->status,
                        'due_date' => $m->due_date?->toDateString(),
                    ])->values()->all(),
                    'tasks' => $taskTitles,
                    'task_count' => count($taskTitles),
                    'milestone_count' => $project->milestones->count(),
                ],
                'schedule_snapshot' => [
                    'start_date' => $project->start_date?->toDateString(),
                    'planned_end_date' => $project->planned_end_date?->toDateString(),
                    'actual_end_date' => $project->actual_end_date?->toDateString(),
                ],
                'budget_snapshot' => [
                    'estimated_budget' => $project->estimated_budget,
                    'actual_budget' => $project->actual_budget,
                ],
                'progress_snapshot' => [
                    'completion_percentage' => $completion,
                ],
                'created_by' => $actor->id,
                'notes' => $notes,
            ]);

            $baseline = $baseline->fresh(['creator', 'project']);

            $runtime = app(WorkflowRuntimeContext::class);
            event(ProjectBaselineCreated::forModel(
                $baseline,
                ['actor_id' => $actor->id, 'project_id' => $project->id, 'version' => $nextVersion],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            $actor->notify(new CrmNotification(
                title: __('Baseline captured'),
                message: __('Baseline v:version was created for :project.', [
                    'version' => $nextVersion,
                    'project' => $project->name,
                ]),
                actionUrl: Route::has('projects.show') ? route('projects.show', $project) : null,
                organizationId: (int) $project->organization_id,
            ));

            return $baseline;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function compare(ProjectBaseline $baseline, ?Project $project = null): array
    {
        $project = $project ?? $baseline->project()->firstOrFail();
        $project->loadMissing(['milestones']);

        $completion = $this->health()
            ? $this->health()->calculateCompletionPercentage($project)
            : (int) ($project->completion_percentage ?? 0);

        $currentScope = [
            'milestone_count' => $project->milestones->count(),
            'milestone_titles' => $project->milestones->pluck('name')->filter()->values()->all(),
        ];

        $baselineScope = $baseline->scope_snapshot ?? [];
        $baselineSchedule = $baseline->schedule_snapshot ?? [];
        $baselineBudget = $baseline->budget_snapshot ?? [];
        $baselineProgress = $baseline->progress_snapshot ?? [];

        $baselineMilestoneCount = (int) ($baselineScope['milestone_count'] ?? count($baselineScope['milestones'] ?? []));
        $baselineTaskCount = (int) ($baselineScope['task_count'] ?? count($baselineScope['tasks'] ?? []));

        $taskCount = Task::query()
            ->where('organization_id', $project->organization_id)
            ->where(function (Builder $query) use ($project): void {
                $query->where('project_id', $project->id)
                    ->orWhere(function (Builder $inner) use ($project): void {
                        $inner->where('taskable_type', $project->getMorphClass())
                            ->where('taskable_id', $project->id);
                    });
            })
            ->count();

        $plannedEndBaseline = $baselineSchedule['planned_end_date'] ?? null;
        $plannedEndCurrent = $project->planned_end_date?->toDateString();

        $scheduleDriftDays = null;
        if ($plannedEndBaseline && $plannedEndCurrent) {
            $scheduleDriftDays = (int) \Illuminate\Support\Carbon::parse($plannedEndBaseline)
                ->diffInDays(\Illuminate\Support\Carbon::parse($plannedEndCurrent), false);
        }

        $estimatedBaseline = (float) ($baselineBudget['estimated_budget'] ?? 0);
        $estimatedCurrent = (float) ($project->estimated_budget ?? 0);
        $actualCurrent = (float) ($project->actual_budget ?? 0);

        $progressBaseline = (int) ($baselineProgress['completion_percentage'] ?? 0);

        return [
            'baseline_id' => $baseline->id,
            'version' => $baseline->version,
            'scope' => [
                'baseline_milestone_count' => $baselineMilestoneCount,
                'current_milestone_count' => $currentScope['milestone_count'],
                'baseline_task_count' => $baselineTaskCount,
                'current_task_count' => $taskCount,
                'milestone_delta' => $currentScope['milestone_count'] - $baselineMilestoneCount,
                'task_delta' => $taskCount - $baselineTaskCount,
            ],
            'schedule' => [
                'baseline_planned_end_date' => $plannedEndBaseline,
                'current_planned_end_date' => $plannedEndCurrent,
                'drift_days' => $scheduleDriftDays,
            ],
            'budget' => [
                'baseline_estimated' => $estimatedBaseline,
                'current_estimated' => $estimatedCurrent,
                'current_actual' => $actualCurrent,
                'estimated_delta' => round($estimatedCurrent - $estimatedBaseline, 2),
                'actual_vs_baseline_estimated' => round($actualCurrent - $estimatedBaseline, 2),
            ],
            'progress' => [
                'baseline_completion_percentage' => $progressBaseline,
                'current_completion_percentage' => $completion,
                'delta' => $completion - $progressBaseline,
            ],
        ];
    }

    protected function health(): ?ProjectHealthService
    {
        if ($this->health !== null) {
            return $this->health;
        }

        if (! class_exists(ProjectHealthService::class)) {
            return null;
        }

        return $this->health = app(ProjectHealthService::class);
    }
}
