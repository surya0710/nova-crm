<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Org-level planning reports (Release 1.2.2) — mirrors attendance report architecture.
 */
class PlanningReportService
{
    public function __construct(
        protected WorkloadService $workload,
        protected ProjectHealthService $health,
        protected MilestoneProgressService $milestones,
        protected ProjectStatisticsService $statistics,
    ) {}

    /**
     * @return list<array{type: string, label: string}>
     */
    public function availableReports(): array
    {
        return collect(config('projects.planning_reports.types', []))
            ->map(fn (string $label, string $type) => ['type' => $type, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function compile(Organization $organization, string $reportType, array $filters = []): array
    {
        $types = config('projects.planning_reports.types', []);
        if (! array_key_exists($reportType, $types)) {
            throw ValidationException::withMessages([
                'report_type' => __('Invalid report type.'),
            ]);
        }

        $from = ! empty($filters['from'])
            ? Carbon::parse($filters['from'])->startOfDay()
            : now()->startOfMonth();
        $to = ! empty($filters['to'])
            ? Carbon::parse($filters['to'])->startOfDay()
            : now()->endOfMonth()->startOfDay();

        $projectId = ! empty($filters['project_id']) ? (int) $filters['project_id'] : null;
        $project = $projectId
            ? Project::query()->where('organization_id', $organization->id)->find($projectId)
            : null;

        [$columns, $rows] = match ($reportType) {
            'resource_allocation' => $this->resourceAllocation($organization, $from, $to, $filters),
            'project_progress' => $this->projectProgress($organization, $project),
            'workload' => $this->workloadRows($organization, $from, $to, $filters),
            'milestone_report' => $this->milestoneRows($organization, $project),
            default => [[], []],
        };

        return [
            'report_type' => $reportType,
            'report_label' => $types[$reportType],
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'project_id' => $projectId,
                'department_id' => $filters['department_id'] ?? null,
                'branch_id' => $filters['branch_id'] ?? null,
            ],
            'generated_at' => now()->toIso8601String(),
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: list<array{key: string, label: string}>, 1: list<array<string, mixed>>}
     */
    protected function resourceAllocation(Organization $organization, Carbon $from, Carbon $to, array $filters): array
    {
        $rows = $this->workload->allocationDashboard($organization, $from, $to, $filters);

        return [
            [
                ['key' => 'employee_name', 'label' => __('Employee')],
                ['key' => 'active_projects', 'label' => __('Projects')],
                ['key' => 'active_tasks', 'label' => __('Tasks')],
                ['key' => 'estimated_hours', 'label' => __('Estimated Hours')],
                ['key' => 'logged_hours', 'label' => __('Logged Hours')],
                ['key' => 'remaining_hours', 'label' => __('Remaining Hours')],
                ['key' => 'capacity_percentage', 'label' => __('Capacity %')],
                ['key' => 'display_status', 'label' => __('Status')],
            ],
            collect($rows)->map(fn (array $row) => [
                'employee_name' => $row['employee_name'],
                'active_projects' => $row['active_projects'],
                'active_tasks' => $row['active_tasks'],
                'estimated_hours' => $row['estimated_hours'],
                'logged_hours' => $row['logged_hours'],
                'remaining_hours' => $row['remaining_hours'],
                'capacity_percentage' => $row['capacity_percentage'],
                'display_status' => $row['display_status'],
            ])->all(),
        ];
    }

    /**
     * @return array{0: list<array{key: string, label: string}>, 1: list<array<string, mixed>>}
     */
    protected function projectProgress(Organization $organization, ?Project $project): array
    {
        $projects = $project
            ? collect([$project])
            : Project::query()
                ->where('organization_id', $organization->id)
                ->where('is_archived', false)
                ->orderBy('name')
                ->limit(100)
                ->get();

        $rows = $projects->map(function (Project $item) {
            $stats = $this->statistics->forProject($item);
            $snapshot = $this->health->latest($item) ?? $this->health->calculate($item);

            return [
                'project' => $item->name,
                'progress' => $snapshot->completion_percentage,
                'health' => $snapshot->health_status_label,
                'open_tasks' => $stats['tasks']['open'] ?? 0,
                'completed_tasks' => $stats['tasks']['closed'] ?? 0,
                'delayed_tasks' => $stats['tasks']['overdue'] ?? 0,
                'hours_logged' => $stats['hours']['actual'] ?? 0,
                'hours_remaining' => $stats['hours']['remaining'] ?? 0,
            ];
        })->all();

        return [
            [
                ['key' => 'project', 'label' => __('Project')],
                ['key' => 'progress', 'label' => __('Progress %')],
                ['key' => 'health', 'label' => __('Health')],
                ['key' => 'open_tasks', 'label' => __('Open Tasks')],
                ['key' => 'completed_tasks', 'label' => __('Completed')],
                ['key' => 'delayed_tasks', 'label' => __('Delayed')],
                ['key' => 'hours_logged', 'label' => __('Hours Logged')],
                ['key' => 'hours_remaining', 'label' => __('Remaining Hours')],
            ],
            $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: list<array{key: string, label: string}>, 1: list<array<string, mixed>>}
     */
    protected function workloadRows(Organization $organization, Carbon $from, Carbon $to, array $filters): array
    {
        return $this->resourceAllocation($organization, $from, $to, $filters);
    }

    /**
     * @return array{0: list<array{key: string, label: string}>, 1: list<array<string, mixed>>}
     */
    protected function milestoneRows(Organization $organization, ?Project $project): array
    {
        $projects = $project
            ? collect([$project])
            : Project::query()
                ->where('organization_id', $organization->id)
                ->where('is_archived', false)
                ->with('milestones')
                ->orderBy('name')
                ->limit(50)
                ->get();

        $rows = [];
        foreach ($projects as $item) {
            foreach ($this->milestones->forProject($item) as $row) {
                $rows[] = [
                    'project' => $item->name,
                    'milestone' => $row['name'],
                    'progress_percentage' => $row['progress_percentage'] ?? $row['actual_progress'],
                    'tasks_total' => $row['tasks_total'] ?? 0,
                    'tasks_completed' => $row['tasks_completed'] ?? 0,
                    'remaining_tasks' => $row['remaining_tasks'],
                    'target_date' => $row['target_date'] ?? '',
                    'overdue' => ($row['is_overdue'] ?? $row['is_delayed']) ? __('Yes') : __('No'),
                ];
            }
        }

        return [
            [
                ['key' => 'project', 'label' => __('Project')],
                ['key' => 'milestone', 'label' => __('Milestone')],
                ['key' => 'progress_percentage', 'label' => __('Progress %')],
                ['key' => 'tasks_total', 'label' => __('Tasks')],
                ['key' => 'tasks_completed', 'label' => __('Completed')],
                ['key' => 'remaining_tasks', 'label' => __('Remaining')],
                ['key' => 'target_date', 'label' => __('Target Date')],
                ['key' => 'overdue', 'label' => __('Overdue')],
            ],
            $rows,
        ];
    }
}
