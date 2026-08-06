<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectBaseline;

class VarianceAnalysisService
{
    public function __construct(
        protected BaselineService $baselines,
    ) {}

    /**
     * Compare latest (or given) baseline vs current project state.
     *
     * @return array<string, mixed>|null
     */
    public function forProject(Project $project, ?ProjectBaseline $baseline = null): ?array
    {
        $baseline ??= ProjectBaseline::query()
            ->where('project_id', $project->id)
            ->orderByDesc('version')
            ->first();

        if (! $baseline) {
            return null;
        }

        return $this->compare($baseline, $project);
    }

    /**
     * @return array<string, mixed>
     */
    public function compare(ProjectBaseline $baseline, ?Project $project = null): array
    {
        $project = $project ?? $baseline->project()->firstOrFail();
        $raw = $this->baselines->compare($baseline, $project);

        $scheduleDriftDays = $raw['schedule']['drift_days'];
        $scheduleDriftPercent = $this->scheduleDriftPercent($baseline, $project, $scheduleDriftDays);

        $budgetBaseline = (float) ($raw['budget']['baseline_estimated'] ?? 0);
        $budgetCurrent = (float) ($raw['budget']['current_actual'] ?? 0);
        $budgetDriftAmount = (float) ($raw['budget']['actual_vs_baseline_estimated'] ?? 0);
        $budgetDriftPercent = $budgetBaseline > 0
            ? round(($budgetDriftAmount / $budgetBaseline) * 100, 2)
            : null;

        $scopeBaseline = max(1, (int) ($raw['scope']['baseline_milestone_count'] ?? 0) + (int) ($raw['scope']['baseline_task_count'] ?? 0));
        $scopeCurrent = (int) ($raw['scope']['current_milestone_count'] ?? 0) + (int) ($raw['scope']['current_task_count'] ?? 0);
        $scopeDriftPercent = round((($scopeCurrent - $scopeBaseline) / $scopeBaseline) * 100, 2);

        $progressBaseline = (int) ($raw['progress']['baseline_completion_percentage'] ?? 0);
        $progressCurrent = (int) ($raw['progress']['current_completion_percentage'] ?? 0);
        // Expected progress vs baseline capture: negative delta means behind baseline expectation if time advanced.
        $progressDriftPercent = $progressBaseline > 0
            ? round((($progressCurrent - $progressBaseline) / max(1, $progressBaseline)) * 100, 2)
            : (float) ($progressCurrent - $progressBaseline);

        $expectedProgress = $this->expectedProgressSinceBaseline($baseline, $project);
        $progressVsExpected = $expectedProgress !== null
            ? round($progressCurrent - $expectedProgress, 2)
            : null;

        $flags = [];

        if ($scheduleDriftPercent !== null && $scheduleDriftPercent >= 10) {
            $flags[] = 'schedule_slip';
        }

        if ($budgetDriftPercent !== null && $budgetDriftPercent >= 10) {
            $flags[] = 'budget_overrun';
        }

        if ($scopeDriftPercent >= 15) {
            $flags[] = 'scope_creep';
        } elseif ($scopeDriftPercent <= -15) {
            $flags[] = 'scope_reduction';
        }

        if ($progressVsExpected !== null && $progressVsExpected <= -10) {
            $flags[] = 'progress_behind';
        } elseif ($progressDriftPercent <= -20) {
            $flags[] = 'progress_behind';
        }

        return [
            'baseline_id' => $baseline->id,
            'project_id' => $project->id,
            'version' => $baseline->version,
            'schedule' => [
                'drift_days' => $scheduleDriftDays,
                'drift_percent' => $scheduleDriftPercent,
                'baseline_planned_end_date' => $raw['schedule']['baseline_planned_end_date'] ?? null,
                'current_planned_end_date' => $raw['schedule']['current_planned_end_date'] ?? null,
            ],
            'budget' => [
                'drift_amount' => $budgetDriftAmount,
                'drift_percent' => $budgetDriftPercent,
                'baseline_estimated' => $budgetBaseline,
                'current_actual' => $budgetCurrent,
            ],
            'scope' => [
                'drift_percent' => $scopeDriftPercent,
                'baseline_units' => $scopeBaseline,
                'current_units' => $scopeCurrent,
                'milestone_delta' => $raw['scope']['milestone_delta'] ?? 0,
                'task_delta' => $raw['scope']['task_delta'] ?? 0,
            ],
            'progress' => [
                'drift_percent' => $progressDriftPercent,
                'baseline_completion_percentage' => $progressBaseline,
                'current_completion_percentage' => $progressCurrent,
                'expected_completion_percentage' => $expectedProgress,
                'vs_expected' => $progressVsExpected,
            ],
            'flags' => array_values(array_unique($flags)),
            'raw' => $raw,
        ];
    }

    protected function scheduleDriftPercent(ProjectBaseline $baseline, Project $project, ?int $driftDays): ?float
    {
        if ($driftDays === null) {
            return null;
        }

        $schedule = $baseline->schedule_snapshot ?? [];
        $start = $schedule['start_date'] ?? $project->start_date?->toDateString();
        $end = $schedule['planned_end_date'] ?? null;

        if (! $start || ! $end) {
            return $driftDays !== 0 ? (float) $driftDays : 0.0;
        }

        $plannedDuration = max(1, \Illuminate\Support\Carbon::parse($start)->diffInDays(\Illuminate\Support\Carbon::parse($end)));

        return round(($driftDays / $plannedDuration) * 100, 2);
    }

    protected function expectedProgressSinceBaseline(ProjectBaseline $baseline, Project $project): ?float
    {
        $schedule = $baseline->schedule_snapshot ?? [];
        $start = $schedule['start_date'] ?? $project->start_date?->toDateString();
        $end = $schedule['planned_end_date'] ?? $project->planned_end_date?->toDateString();

        if (! $start || ! $end) {
            return null;
        }

        $startDate = \Illuminate\Support\Carbon::parse($start)->startOfDay();
        $endDate = \Illuminate\Support\Carbon::parse($end)->startOfDay();
        $totalDays = max(1, $startDate->diffInDays($endDate));
        $elapsed = max(0, $startDate->diffInDays(now()->startOfDay()));

        return round(min(100, ($elapsed / $totalDays) * 100), 2);
    }
}
