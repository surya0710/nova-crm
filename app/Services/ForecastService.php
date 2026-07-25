<?php

namespace App\Services;

use App\Events\ForecastGenerated;
use App\Models\Portfolio;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\ProjectRisk;
use App\Models\ResourceAllocation;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ForecastService
{
    public function __construct(
        protected ?ProjectHealthService $health = null,
        protected ?ProjectStatisticsService $statistics = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forProject(Project $project, ?User $actor = null, bool $dispatch = true): array
    {
        $payload = [
            'subject' => 'project',
            'project_id' => $project->id,
            'estimated_completion' => $this->estimatedCompletion($project)?->toDateString(),
            'likely_delay' => $this->likelyDelay($project),
            'budget_overrun' => $this->budgetOverrun($project),
            'risk_forecast' => $this->riskForecast($project),
        ];

        if ($dispatch) {
            $this->dispatchForecast($project, $payload, $actor);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function forPortfolio(Portfolio $portfolio, ?User $actor = null, bool $dispatch = true): array
    {
        $projects = $portfolio->projects()->where('is_archived', false)->get();

        $projectForecasts = $projects->map(fn (Project $project) => $this->forProject($project, $actor, false))->all();

        $payload = [
            'subject' => 'portfolio',
            'portfolio_id' => $portfolio->id,
            'projects' => $projectForecasts,
            'portfolio_capacity' => $this->portfolioCapacity($portfolio, $projects),
            'delayed_project_count' => collect($projectForecasts)->filter(fn ($f) => ($f['likely_delay']['is_likely'] ?? false))->count(),
            'overrun_project_count' => collect($projectForecasts)->filter(fn ($f) => ($f['budget_overrun']['is_likely'] ?? false))->count(),
            'average_risk_score' => round((float) collect($projectForecasts)->avg(fn ($f) => $f['risk_forecast']['score'] ?? 0), 2),
        ];

        if ($dispatch) {
            $this->dispatchForecast($portfolio, $payload, $actor);
        }

        return $payload;
    }

    public function estimatedCompletion(Project $project): ?Carbon
    {
        if ($this->health()) {
            $predicted = $this->health()->predictCompletionDate($project);
            if ($predicted instanceof Carbon) {
                return $predicted->copy()->startOfDay();
            }
            if (is_string($predicted)) {
                return Carbon::parse($predicted)->startOfDay();
            }
        }

        $completion = (int) ($project->completion_percentage ?? 0);
        if ($completion >= 100) {
            return ($project->actual_end_date ?? now())->copy()->startOfDay();
        }

        // Velocity heuristic: tasks completed in last 14 days → remaining days estimate.
        if ($this->statistics()) {
            $stats = $this->statistics()->forProject($project);
            $velocity = (int) ($stats['velocity']['completed_count'] ?? 0);
            $periodDays = (int) ($stats['velocity']['period_days'] ?? 14);
            $openTasks = (int) ($stats['tasks']['open'] ?? 0);

            if ($velocity > 0 && $openTasks > 0) {
                $daysRemaining = (int) ceil(($openTasks / $velocity) * $periodDays);

                return now()->copy()->addDays($daysRemaining)->startOfDay();
            }
        }

        if (! $project->start_date) {
            return $project->planned_end_date?->copy()->startOfDay();
        }

        $elapsed = max(1, $project->start_date->diffInDays(now()));
        $done = max(1, $completion);
        $daysPerPercent = $elapsed / $done;
        $remaining = (int) ceil(max(0, 100 - $completion) * $daysPerPercent);

        return now()->copy()->addDays($remaining)->startOfDay();
    }

    protected function statistics(): ?ProjectStatisticsService
    {
        if ($this->statistics !== null) {
            return $this->statistics;
        }

        if (! class_exists(ProjectStatisticsService::class)) {
            return null;
        }

        return $this->statistics = app(ProjectStatisticsService::class);
    }

    /**
     * @return array{is_likely: bool, days: int|null, estimated_completion: string|null, planned_end_date: string|null}
     */
    public function likelyDelay(Project $project): array
    {
        $estimated = $this->estimatedCompletion($project);
        $planned = $project->planned_end_date;

        if (! $estimated || ! $planned) {
            return [
                'is_likely' => false,
                'days' => null,
                'estimated_completion' => $estimated?->toDateString(),
                'planned_end_date' => $planned?->toDateString(),
            ];
        }

        $days = (int) $planned->startOfDay()->diffInDays($estimated->startOfDay(), false);

        return [
            'is_likely' => $days > 0,
            'days' => $days,
            'estimated_completion' => $estimated->toDateString(),
            'planned_end_date' => $planned->toDateString(),
        ];
    }

    /**
     * @return array{is_likely: bool, planned: float, actual: float, forecast: float, overrun_amount: float, overrun_percent: float|null}
     */
    public function budgetOverrun(Project $project): array
    {
        $budget = ProjectBudget::query()
            ->where('project_id', $project->id)
            ->latest('id')
            ->first();

        if ($budget) {
            $planned = (float) $budget->planned_total;
            $actual = (float) $budget->actual_total;
            $forecast = (float) $budget->forecast_total;
        } else {
            $planned = (float) ($project->estimated_budget ?? 0);
            $actual = (float) ($project->actual_budget ?? 0);
            $completion = max(1, (int) ($project->completion_percentage ?? 0));
            $forecast = $completion > 0 && $actual > 0
                ? round($actual / ($completion / 100), 2)
                : $planned;
        }

        $overrunAmount = round(max(0, $forecast - $planned), 2);
        $overrunPercent = $planned > 0 ? round(($overrunAmount / $planned) * 100, 2) : null;

        return [
            'is_likely' => $overrunAmount > 0 && ($overrunPercent === null || $overrunPercent >= 5),
            'planned' => $planned,
            'actual' => $actual,
            'forecast' => $forecast,
            'overrun_amount' => $overrunAmount,
            'overrun_percent' => $overrunPercent,
        ];
    }

    /**
     * @return array{score: float, open_count: int, high_severity_count: int, outlook: string}
     */
    public function riskForecast(Project $project): array
    {
        $risks = ProjectRisk::query()
            ->where('organization_id', $project->organization_id)
            ->where('project_id', $project->id)
            ->whereNotIn('status', ['closed', 'mitigated', 'accepted'])
            ->get(['severity', 'status']);

        $score = $risks->isEmpty() ? 0.0 : round((float) $risks->avg('severity'), 2);
        $high = $risks->filter(fn (ProjectRisk $r) => (int) $r->severity >= 15)->count();

        $outlook = match (true) {
            $score >= 15 || $high >= 2 => 'critical',
            $score >= 9 || $high >= 1 => 'elevated',
            $score > 0 => 'moderate',
            default => 'low',
        };

        return [
            'score' => $score,
            'open_count' => $risks->count(),
            'high_severity_count' => $high,
            'outlook' => $outlook,
        ];
    }

    /**
     * @param  Collection<int, Project>|null  $projects
     * @return array<string, mixed>
     */
    public function portfolioCapacity(Portfolio $portfolio, ?Collection $projects = null): array
    {
        $projects ??= $portfolio->projects()->where('is_archived', false)->get();
        $projectIds = $projects->pluck('id');

        $allocations = ResourceAllocation::query()
            ->where('organization_id', $portfolio->organization_id)
            ->whereIn('project_id', $projectIds)
            ->get(['employee_id', 'allocation_percentage', 'planned_hours']);

        $totalAllocation = (float) $allocations->sum('allocation_percentage');
        $uniquePeople = $allocations->pluck('employee_id')->filter()->unique()->count();
        $avgPerPerson = $uniquePeople > 0 ? round($totalAllocation / $uniquePeople, 2) : 0.0;

        return [
            'project_count' => $projects->count(),
            'allocated_people' => $uniquePeople,
            'total_allocation_percentage' => round($totalAllocation, 2),
            'average_allocation_per_person' => $avgPerPerson,
            'planned_hours' => round((float) $allocations->sum('planned_hours'), 2),
            'capacity_pressure' => match (true) {
                $avgPerPerson >= 100 => 'overallocated',
                $avgPerPerson >= 80 => 'tight',
                $avgPerPerson >= 50 => 'balanced',
                default => 'available',
            },
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function dispatchForecast(Project|Portfolio $subject, array $payload, ?User $actor): void
    {
        $runtime = app(WorkflowRuntimeContext::class);
        event(ForecastGenerated::forModel(
            $subject->fresh(),
            [
                'actor_id' => $actor?->id,
                'forecast' => $payload,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));
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
