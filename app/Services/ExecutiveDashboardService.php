<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\ProjectHealthSnapshot;
use App\Models\ProjectMilestone;
use App\Models\ProjectRisk;
use App\Models\User;
use Illuminate\Support\Collection;

class ExecutiveDashboardService
{
    public function __construct(
        protected ProjectHealthService $health,
        protected PortfolioStatisticsService $portfolioStatistics,
        protected ForecastService $forecast,
        protected RiskManagementService $risks,
    ) {}

    /**
     * Read-only org-wide executive payload.
     *
     * @return array<string, mixed>
     */
    public function forOrganization(Organization $organization, ?User $actor = null): array
    {
        $portfolioHealth = $this->health->portfolioSummary($organization);

        $activeProjects = Project::query()
            ->where('organization_id', $organization->id)
            ->where('is_archived', false)
            ->with(['status', 'department', 'manager'])
            ->get();

        $latestSnapshots = $this->latestSnapshots($organization, $activeProjects->pluck('id'));

        $atRisk = $latestSnapshots
            ->filter(fn (ProjectHealthSnapshot $s) => in_array($s->health_status, ['at_risk', 'delayed'], true))
            ->values();

        $delayed = $latestSnapshots
            ->filter(fn (ProjectHealthSnapshot $s) => $s->health_status === 'delayed')
            ->values();

        $avgProgress = $activeProjects->isEmpty()
            ? 0.0
            : round((float) $activeProjects->avg(fn (Project $p) => $this->health->calculateCompletionPercentage($p)), 2);

        $budgetStatus = $this->budgetStatus($organization, $activeProjects);
        $upcomingMilestones = $this->upcomingMilestones($organization);
        $riskOverview = $this->riskOverview($organization);
        $departmentPerformance = $this->departmentPerformance($activeProjects, $latestSnapshots);
        $deliveryForecast = $this->deliveryForecast($organization, $activeProjects);

        $portfolios = Portfolio::query()
            ->where('organization_id', $organization->id)
            ->whereNull('archived_at')
            ->with('projects')
            ->get()
            ->map(fn (Portfolio $portfolio) => [
                'id' => $portfolio->id,
                'name' => $portfolio->name,
                'code' => $portfolio->code,
                'stats' => $this->portfolioStatistics->forPortfolio($portfolio),
            ])
            ->values()
            ->all();

        return [
            'organization_id' => $organization->id,
            'portfolio_health' => $portfolioHealth,
            'progress' => [
                'average_completion_percentage' => $avgProgress,
                'active_project_count' => $activeProjects->count(),
            ],
            'budget_status' => $budgetStatus,
            'at_risk_projects' => $atRisk->map(fn (ProjectHealthSnapshot $s) => [
                'project_id' => $s->project_id,
                'name' => $s->project?->name,
                'health_status' => $s->health_status,
                'completion_percentage' => $s->completion_percentage,
            ])->all(),
            'delayed_projects' => $delayed->map(fn (ProjectHealthSnapshot $s) => [
                'project_id' => $s->project_id,
                'name' => $s->project?->name,
                'schedule_variance' => $s->schedule_variance,
            ])->all(),
            'upcoming_milestones' => $upcomingMilestones,
            'risk_overview' => $riskOverview,
            'kpis' => [
                'active_projects' => $activeProjects->count(),
                'at_risk_count' => ($portfolioHealth['at_risk'] ?? 0) + ($portfolioHealth['delayed'] ?? 0),
                'on_track_count' => $portfolioHealth['on_track'] ?? 0,
                'completed_count' => $portfolioHealth['completed'] ?? 0,
                'open_risks' => $riskOverview['open_count'],
                'budget_variance_total' => $budgetStatus['variance_total'],
                'average_completion_percentage' => $avgProgress,
            ],
            'department_performance' => $departmentPerformance,
            'delivery_forecast' => $deliveryForecast,
            'portfolios' => $portfolios,
        ];
    }

    /**
     * @param  Collection<int, int|string>  $projectIds
     * @return Collection<int, ProjectHealthSnapshot>
     */
    protected function latestSnapshots(Organization $organization, Collection $projectIds): Collection
    {
        if ($projectIds->isEmpty()) {
            return collect();
        }

        $latestIds = ProjectHealthSnapshot::query()
            ->where('organization_id', $organization->id)
            ->whereIn('project_id', $projectIds)
            ->selectRaw('MAX(id) as id')
            ->groupBy('project_id')
            ->pluck('id');

        if ($latestIds->isEmpty()) {
            return collect();
        }

        return ProjectHealthSnapshot::query()
            ->whereIn('id', $latestIds)
            ->with('project')
            ->get();
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return array<string, float|string>
     */
    protected function budgetStatus(Organization $organization, Collection $projects): array
    {
        $budgets = ProjectBudget::query()
            ->where('organization_id', $organization->id)
            ->whereIn('project_id', $projects->pluck('id'))
            ->get();

        if ($budgets->isNotEmpty()) {
            $planned = round((float) $budgets->sum('planned_total'), 2);
            $actual = round((float) $budgets->sum('actual_total'), 2);
            $forecast = round((float) $budgets->sum('forecast_total'), 2);
            $variance = round((float) $budgets->sum('variance_total'), 2);
        } else {
            $planned = round((float) $projects->sum('estimated_budget'), 2);
            $actual = round((float) $projects->sum('actual_budget'), 2);
            $forecast = $planned;
            $variance = round($actual - $planned, 2);
        }

        $status = match (true) {
            $planned <= 0 => 'unknown',
            abs($variance) / $planned > 0.1 => 'over_threshold',
            $variance > 0 => 'over',
            $variance < 0 => 'under',
            default => 'on_plan',
        };

        return [
            'planned_total' => $planned,
            'actual_total' => $actual,
            'forecast_total' => $forecast,
            'variance_total' => $variance,
            'status' => $status,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function upcomingMilestones(Organization $organization, int $days = 30): array
    {
        $from = now()->toDateString();
        $to = now()->addDays($days)->toDateString();

        return ProjectMilestone::query()
            ->whereHas('project', fn ($q) => $q->where('organization_id', $organization->id)->where('is_archived', false))
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$from, $to])
            ->with('project:id,name')
            ->orderBy('due_date')
            ->limit(25)
            ->get()
            ->map(fn (ProjectMilestone $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'due_date' => $m->due_date?->toDateString(),
                'project_id' => $m->project_id,
                'project_name' => $m->project?->name,
                'status' => $m->status,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function riskOverview(Organization $organization): array
    {
        $open = ProjectRisk::query()
            ->where('organization_id', $organization->id)
            ->whereNotIn('status', ['closed', 'mitigated', 'accepted'])
            ->get(['severity', 'status']);

        $matrix = $this->risks->matrix($organization);

        return [
            'open_count' => $open->count(),
            'average_severity' => $open->isEmpty() ? 0.0 : round((float) $open->avg('severity'), 2),
            'high_severity_count' => $open->filter(fn (ProjectRisk $r) => (int) $r->severity >= 15)->count(),
            'escalated_count' => $open->where('status', 'escalated')->count(),
            'matrix' => $matrix,
        ];
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @param  Collection<int, ProjectHealthSnapshot>  $snapshots
     * @return list<array<string, mixed>>
     */
    protected function departmentPerformance(Collection $projects, Collection $snapshots): array
    {
        $snapshotByProject = $snapshots->keyBy('project_id');

        return $projects
            ->groupBy('department_id')
            ->map(function (Collection $group, $departmentId) use ($snapshotByProject) {
                $healthCounts = ['on_track' => 0, 'at_risk' => 0, 'delayed' => 0, 'completed' => 0];
                foreach ($group as $project) {
                    $status = $snapshotByProject->get($project->id)?->health_status ?? 'on_track';
                    if (array_key_exists($status, $healthCounts)) {
                        $healthCounts[$status]++;
                    }
                }

                return [
                    'department_id' => $departmentId ? (int) $departmentId : null,
                    'department_name' => $group->first()?->department?->name ?? __('Unassigned'),
                    'project_count' => $group->count(),
                    'average_completion_percentage' => round((float) $group->avg('completion_percentage'), 2),
                    'health' => $healthCounts,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return array<string, mixed>
     */
    protected function deliveryForecast(Organization $organization, Collection $projects): array
    {
        $likelyDelayed = 0;
        $onTime = 0;
        $estimates = [];

        foreach ($projects->take(100) as $project) {
            $delay = $this->forecast->likelyDelay($project);
            if ($delay['is_likely']) {
                $likelyDelayed++;
            } else {
                $onTime++;
            }
            if ($delay['estimated_completion']) {
                $estimates[] = $delay['estimated_completion'];
            }
        }

        sort($estimates);

        return [
            'on_time_count' => $onTime,
            'likely_delayed_count' => $likelyDelayed,
            'next_estimated_completion' => $estimates[0] ?? null,
            'median_estimated_completion' => $estimates !== []
                ? $estimates[(int) floor((count($estimates) - 1) / 2)]
                : null,
        ];
    }
}
