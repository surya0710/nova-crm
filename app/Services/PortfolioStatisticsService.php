<?php

namespace App\Services;

use App\Events\PortfolioHealthUpdated;
use App\Models\Portfolio;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\ProjectHealthSnapshot;
use App\Models\ProjectRisk;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Support\Collection;

class PortfolioStatisticsService
{
    public function __construct(
        protected ?ProjectHealthService $health = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forPortfolio(Portfolio $portfolio, ?User $actor = null, bool $dispatchHealthEvent = false): array
    {
        $projects = $portfolio->relationLoaded('projects')
            ? $portfolio->projects
            : $portfolio->projects()->with('status')->get();

        $progress = $this->averageProgress($projects);
        $healthRollup = $this->healthRollup($portfolio, $projects);
        $budget = $this->budgetSummary($portfolio, $projects);
        $countsByStatus = $this->projectCountsByStatus($projects);
        $riskScore = $this->riskScore($portfolio);

        $payload = [
            'portfolio_id' => $portfolio->id,
            'project_count' => $projects->count(),
            'average_completion_percentage' => $progress,
            'health' => $healthRollup,
            'budget' => $budget,
            'projects_by_status' => $countsByStatus,
            'risk_score' => $riskScore,
        ];

        if ($dispatchHealthEvent) {
            $runtime = app(WorkflowRuntimeContext::class);
            event(PortfolioHealthUpdated::forModel(
                $portfolio->fresh(),
                [
                    'actor_id' => $actor?->id,
                    'health' => $healthRollup,
                    'average_completion_percentage' => $progress,
                    'risk_score' => $riskScore,
                ],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));
        }

        return $payload;
    }

    /**
     * @param  Collection<int, Project>  $projects
     */
    public function averageProgress(Collection $projects): float
    {
        if ($projects->isEmpty()) {
            return 0.0;
        }

        $health = $this->health();

        $values = $projects->map(function (Project $project) use ($health) {
            if ($health) {
                return (float) $health->calculateCompletionPercentage($project);
            }

            return (float) ($project->completion_percentage ?? 0);
        });

        return round((float) $values->avg(), 2);
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return array<string, int>
     */
    public function healthRollup(Portfolio $portfolio, Collection $projects): array
    {
        $statuses = array_keys(config('projects.health_statuses', [
            'on_track' => 'On Track',
            'at_risk' => 'At Risk',
            'delayed' => 'Delayed',
            'completed' => 'Completed',
            'archived' => 'Archived',
        ]));
        $counts = array_fill_keys($statuses, 0);

        if ($projects->isEmpty()) {
            return $counts;
        }

        $projectIds = $projects->pluck('id');

        $latestIds = ProjectHealthSnapshot::query()
            ->where('organization_id', $portfolio->organization_id)
            ->whereIn('project_id', $projectIds)
            ->selectRaw('MAX(id) as id')
            ->groupBy('project_id')
            ->pluck('id');

        if ($latestIds->isNotEmpty()) {
            ProjectHealthSnapshot::query()
                ->whereIn('id', $latestIds)
                ->get(['health_status'])
                ->each(function (ProjectHealthSnapshot $snapshot) use (&$counts): void {
                    if (array_key_exists($snapshot->health_status, $counts)) {
                        $counts[$snapshot->health_status]++;
                    }
                });

            $covered = ProjectHealthSnapshot::query()
                ->whereIn('id', $latestIds)
                ->pluck('project_id');
            $missing = $projectIds->diff($covered);
        } else {
            $missing = $projectIds;
        }

        $health = $this->health();
        foreach ($projects->whereIn('id', $missing) as $project) {
            if ($health) {
                $status = $health->determineHealthStatus($project, [
                    'overdue_tasks_count' => $health->detectOverdueTasks($project)->count(),
                    'delayed_milestones_count' => $health->detectDelayedMilestones($project)->count(),
                    'schedule_variance_days' => 0,
                ]);
            } else {
                $status = $project->isArchived() ? 'archived' : 'on_track';
            }

            if (array_key_exists($status, $counts)) {
                $counts[$status]++;
            }
        }

        return $counts;
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return array<string, float|int>
     */
    public function budgetSummary(Portfolio $portfolio, Collection $projects): array
    {
        $projectIds = $projects->pluck('id');

        $budgets = ProjectBudget::query()
            ->where('organization_id', $portfolio->organization_id)
            ->whereIn('project_id', $projectIds)
            ->get(['planned_total', 'actual_total', 'forecast_total', 'variance_total']);

        if ($budgets->isNotEmpty()) {
            return [
                'planned_total' => round((float) $budgets->sum('planned_total'), 2),
                'actual_total' => round((float) $budgets->sum('actual_total'), 2),
                'forecast_total' => round((float) $budgets->sum('forecast_total'), 2),
                'variance_total' => round((float) $budgets->sum('variance_total'), 2),
                'source' => 'project_budgets',
            ];
        }

        return [
            'planned_total' => round((float) $projects->sum('estimated_budget'), 2),
            'actual_total' => round((float) $projects->sum('actual_budget'), 2),
            'forecast_total' => round((float) $projects->sum('estimated_budget'), 2),
            'variance_total' => round((float) $projects->sum(fn (Project $p) => (float) ($p->actual_budget ?? 0) - (float) ($p->estimated_budget ?? 0)), 2),
            'source' => 'project_fields',
        ];
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return array<string, int>
     */
    public function projectCountsByStatus(Collection $projects): array
    {
        $counts = [];

        foreach ($projects as $project) {
            $key = $project->status?->slug ?? ($project->is_archived ? 'archived' : 'unknown');
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Weighted open-risk score for the portfolio (avg severity of open risks, 0–25 scale).
     */
    public function riskScore(Portfolio $portfolio): float
    {
        $projectIds = $portfolio->projects()->pluck('projects.id');

        $risks = ProjectRisk::query()
            ->where('organization_id', $portfolio->organization_id)
            ->where(function ($query) use ($portfolio, $projectIds) {
                $query->where('portfolio_id', $portfolio->id)
                    ->orWhereIn('project_id', $projectIds);
            })
            ->whereNotIn('status', ['closed', 'mitigated', 'accepted'])
            ->get(['severity']);

        if ($risks->isEmpty()) {
            return 0.0;
        }

        return round((float) $risks->avg('severity'), 2);
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
