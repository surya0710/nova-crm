<?php

namespace App\Services;

use App\Events\ReportGenerated;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectReport;
use App\Models\ResourceAllocation;
use App\Models\Task;
use App\Models\TaskTimeLog;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Workflow\WorkflowRuntimeContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProjectReportingService
{
    public function __construct(
        protected ProjectStatisticsService $statistics,
        protected TimelineService $timeline,
        protected MilestoneProgressService $milestoneProgress,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function generate(
        ?Project $project,
        Organization $organization,
        string $reportType,
        string $format,
        array $filters,
        User $actor,
    ): ProjectReport {
        $this->validateReportType($reportType);
        $this->validateFormat($format);

        $payload = $this->buildPayload($reportType, $project, $organization, $filters);
        $storagePath = $this->exportPayload($payload, $reportType, $format, $organization, $project);

        $report = ProjectReport::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project?->id,
            'report_type' => $reportType,
            'generated_by' => $actor->id,
            'filters' => $filters,
            'storage_path' => $storagePath,
            'generated_at' => now(),
        ]);

        $runtime = app(WorkflowRuntimeContext::class);

        if ($project) {
            event(ReportGenerated::forModel(
                $project->fresh(),
                [
                    'actor_id' => $actor->id,
                    'report_id' => $report->id,
                    'report_type' => $reportType,
                    'format' => $format,
                ],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));
        }

        $this->notifyGenerator($project, $organization, $actor, $report);

        return $report->fresh(['generator', 'project']);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function buildPayload(
        string $reportType,
        ?Project $project,
        Organization $organization,
        array $filters,
    ): array {
        return match ($reportType) {
            'summary' => $this->buildSummaryPayload($project, $organization),
            'task_progress', 'project_progress' => $this->buildTaskProgressPayload($project, $organization, $filters),
            'resource_utilization', 'resource_allocation' => $this->buildResourceUtilizationPayload($project, $organization, $filters),
            'workload' => $this->buildWorkloadPayload($project, $organization, $filters),
            'milestone_status', 'milestone_report' => $this->buildMilestoneStatusPayload($project, $organization),
            'time_tracking' => $this->buildTimeTrackingPayload($project, $organization, $filters),
            'timeline' => $this->buildTimelinePayload($project, $organization),
            'executive' => $this->buildExecutivePayload($project, $organization),
            default => throw ValidationException::withMessages([
                'report_type' => __('Invalid report type.'),
            ]),
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSummaryPayload(?Project $project, Organization $organization): array
    {
        if (! $project) {
            throw ValidationException::withMessages([
                'project' => __('A project is required for this report type.'),
            ]);
        }

        return [
            'title' => __('Project Summary: :name', ['name' => $project->name]),
            'project' => $project->only([
                'id', 'name', 'project_number', 'priority', 'start_date',
                'planned_end_date', 'completion_percentage',
            ]),
            'statistics' => $this->statistics->forProject($project),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function buildTaskProgressPayload(?Project $project, Organization $organization, array $filters): array
    {
        $query = $this->scopedTasksQuery($project, $organization);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $tasks = $query->get();

        return [
            'title' => __('Task Progress Report'),
            'rows' => $tasks->map(fn (Task $task) => [
                $task->task_number ?? $task->id,
                $task->title,
                $task->status,
                $task->completion_percentage,
                $task->due_date?->toDateString() ?? '',
                $task->assignee?->name ?? '',
            ])->all(),
            'headers' => [__('Task #'), __('Title'), __('Status'), __('Progress %'), __('Due Date'), __('Assignee')],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function buildResourceUtilizationPayload(?Project $project, Organization $organization, array $filters): array
    {
        $query = ResourceAllocation::query()
            ->where('organization_id', $organization->id)
            ->with(['employee', 'project']);

        if ($project) {
            $query->where('project_id', $project->id);
        }

        $allocations = $query->get();

        return [
            'title' => __('Resource Utilization Report'),
            'headers' => [__('Employee'), __('Project'), __('Allocation %'), __('Planned Hours'), __('Start'), __('End')],
            'rows' => $allocations->map(fn (ResourceAllocation $alloc) => [
                $alloc->employee?->full_name ?? $alloc->employee?->name ?? '',
                $alloc->project?->name ?? '',
                $alloc->allocation_percentage,
                $alloc->planned_hours,
                $alloc->planned_start_date?->toDateString() ?? '',
                $alloc->planned_end_date?->toDateString() ?? '',
            ])->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function buildWorkloadPayload(?Project $project, Organization $organization, array $filters): array
    {
        $from = ! empty($filters['from'])
            ? \Carbon\Carbon::parse($filters['from'])->startOfDay()
            : now()->startOfWeek();
        $to = ! empty($filters['to'])
            ? \Carbon\Carbon::parse($filters['to'])->startOfDay()
            : now()->endOfWeek()->startOfDay();

        $rows = app(WorkloadService::class)->allocationDashboard($organization, $from, $to, [
            'project_id' => $project?->id ?? ($filters['project_id'] ?? null),
            'department_id' => $filters['department_id'] ?? null,
            'branch_id' => $filters['branch_id'] ?? null,
        ]);

        return [
            'title' => __('Workload Report'),
            'headers' => [
                __('Employee'),
                __('Projects'),
                __('Tasks'),
                __('Estimated Hours'),
                __('Logged Hours'),
                __('Remaining Hours'),
                __('Capacity %'),
                __('Status'),
            ],
            'rows' => collect($rows)->map(fn (array $row) => [
                $row['employee_name'],
                $row['active_projects'],
                $row['active_tasks'],
                $row['estimated_hours'],
                $row['logged_hours'],
                $row['remaining_hours'],
                $row['capacity_percentage'],
                $row['display_status'],
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildMilestoneStatusPayload(?Project $project, Organization $organization): array
    {
        if (! $project) {
            throw ValidationException::withMessages([
                'project' => __('A project is required for this report type.'),
            ]);
        }

        $rows = collect($this->milestoneProgress->forProject($project));

        return [
            'title' => __('Milestone Status Report'),
            'headers' => [
                __('Milestone'),
                __('Progress %'),
                __('Tasks'),
                __('Completed'),
                __('Remaining'),
                __('Target Date'),
                __('Overdue'),
            ],
            'rows' => $rows->map(fn (array $row) => [
                $row['name'],
                $row['progress_percentage'] ?? $row['actual_progress'],
                $row['tasks_total'] ?? 0,
                $row['tasks_completed'] ?? 0,
                $row['remaining_tasks'],
                $row['target_date'] ?? '',
                ($row['is_overdue'] ?? $row['is_delayed']) ? __('Yes') : __('No'),
            ])->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function buildTimeTrackingPayload(?Project $project, Organization $organization, array $filters): array
    {
        $taskIds = $this->scopedTasksQuery($project, $organization)->pluck('id');

        $logs = TaskTimeLog::query()
            ->where('organization_id', $organization->id)
            ->whereIn('task_id', $taskIds)
            ->with(['task', 'user'])
            ->latest('start_time')
            ->get();

        return [
            'title' => __('Time Tracking Report'),
            'headers' => [__('Task'), __('User'), __('Start'), __('End'), __('Duration (min)')],
            'rows' => $logs->map(fn (TaskTimeLog $log) => [
                $log->task?->title ?? '',
                $log->user?->name ?? '',
                $log->start_time?->toDateTimeString() ?? '',
                $log->end_time?->toDateTimeString() ?? '',
                $log->duration_minutes,
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildTimelinePayload(?Project $project, Organization $organization): array
    {
        if (! $project) {
            throw ValidationException::withMessages([
                'project' => __('A project is required for this report type.'),
            ]);
        }

        return [
            'title' => __('Project Timeline Report'),
            'timeline' => $this->timeline->build($project),
            'gantt' => $this->timeline->gantt($project),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildExecutivePayload(?Project $project, Organization $organization): array
    {
        if (! $project) {
            throw ValidationException::withMessages([
                'project' => __('A project is required for this report type.'),
            ]);
        }

        return [
            'title' => __('Executive Summary: :name', ['name' => $project->name]),
            'project' => $project->only(['name', 'completion_percentage', 'planned_end_date', 'priority']),
            'statistics' => $this->statistics->forProject($project),
            'critical_milestones' => $this->timeline->criticalMilestones($project),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function exportPayload(
        array $payload,
        string $reportType,
        string $format,
        Organization $organization,
        ?Project $project,
    ): string {
        $directory = 'project-reports/'.$organization->id;
        Storage::makeDirectory($directory);

        $basename = $reportType.'-'.($project?->id ?? 'org').'-'.now()->format('Ymd-His');

        return match ($format) {
            'csv' => $this->exportCsv($directory, $basename, $payload),
            'pdf' => $this->exportPdf($directory, $basename, $payload),
            'excel' => $this->exportExcel($directory, $basename, $payload),
            default => $this->exportCsv($directory, $basename, $payload),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function exportCsv(string $directory, string $basename, array $payload): string
    {
        $path = $directory.'/'.$basename.'.csv';
        $handle = fopen(Storage::path($path), 'w');

        fputcsv($handle, [($payload['title'] ?? __('Report'))]);

        if (isset($payload['headers'], $payload['rows'])) {
            fputcsv($handle, $payload['headers']);
            foreach ($payload['rows'] as $row) {
                fputcsv($handle, $row);
            }
        } else {
            $this->writeArrayAsCsv($handle, $payload);
        }

        fclose($handle);

        return $path;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function exportPdf(string $directory, string $basename, array $payload): string
    {
        $html = $this->payloadToHtml($payload);
        $path = $directory.'/'.$basename.'.pdf';

        Pdf::loadHTML($html)->save(Storage::path($path));

        return $path;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function exportExcel(string $directory, string $basename, array $payload): string
    {
        $path = $directory.'/'.$basename.'.xlsx';
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue([1, 1], (string) ($payload['title'] ?? __('Report')));

        $rowIndex = 3;

        if (isset($payload['headers'], $payload['rows'])) {
            foreach (array_values($payload['headers']) as $colIndex => $header) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex], (string) $header);
            }
            $rowIndex++;

            foreach ($payload['rows'] as $row) {
                foreach (array_values($row) as $colIndex => $value) {
                    $sheet->setCellValue([$colIndex + 1, $rowIndex], $value);
                }
                $rowIndex++;
            }
        } else {
            foreach ($this->flattenPayload($payload) as $line) {
                $sheet->setCellValue([1, $rowIndex], $line);
                $rowIndex++;
            }
        }

        (new Xlsx($spreadsheet))->save(Storage::path($path));

        return $path;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function payloadToHtml(array $payload): string
    {
        $title = e((string) ($payload['title'] ?? __('Report')));
        $body = '';

        if (isset($payload['headers'], $payload['rows'])) {
            $body .= '<table border="1" cellpadding="4"><thead><tr>';
            foreach ($payload['headers'] as $header) {
                $body .= '<th>'.e((string) $header).'</th>';
            }
            $body .= '</tr></thead><tbody>';
            foreach ($payload['rows'] as $row) {
                $body .= '<tr>';
                foreach ($row as $cell) {
                    $body .= '<td>'.e((string) $cell).'</td>';
                }
                $body .= '</tr>';
            }
            $body .= '</tbody></table>';
        } else {
            foreach ($this->flattenPayload($payload) as $line) {
                $body .= '<p>'.e($line).'</p>';
            }
        }

        return "<html><head><meta charset=\"utf-8\"><title>{$title}</title></head><body><h1>{$title}</h1>{$body}</body></html>";
    }

    /**
     * @param  resource  $handle
     * @param  array<string, mixed>  $payload
     */
    protected function writeArrayAsCsv($handle, array $payload): void
    {
        foreach ($this->flattenPayload($payload) as $line) {
            fputcsv($handle, [$line]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    protected function flattenPayload(array $payload, string $prefix = ''): array
    {
        $lines = [];

        foreach ($payload as $key => $value) {
            if ($key === 'title') {
                continue;
            }

            $label = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $lines = array_merge($lines, $this->flattenPayload($value, $label));
            } else {
                $lines[] = $label.': '.(string) $value;
            }
        }

        return $lines;
    }

    /**
     * @return Builder<Task>
     */
    protected function scopedTasksQuery(?Project $project, Organization $organization): Builder
    {
        $query = Task::query()->where('organization_id', $organization->id);

        if ($project) {
            $query->where(function (Builder $inner) use ($project): void {
                $inner->where('project_id', $project->id)
                    ->orWhere(function (Builder $morph) use ($project): void {
                        $morph->where('taskable_type', $project->getMorphClass())
                            ->where('taskable_id', $project->id);
                    });
            });
        }

        return $query->with('assignee');
    }

    protected function validateReportType(string $reportType): void
    {
        if (! array_key_exists($reportType, config('projects.report_types', []))) {
            throw ValidationException::withMessages([
                'report_type' => __('Invalid report type.'),
            ]);
        }
    }

    protected function validateFormat(string $format): void
    {
        if (! array_key_exists($format, config('projects.report_formats', []))) {
            throw ValidationException::withMessages([
                'format' => __('Invalid report format.'),
            ]);
        }
    }

    protected function notifyGenerator(
        ?Project $project,
        Organization $organization,
        User $actor,
        ProjectReport $report,
    ): void {
        $actor->notify(new CrmNotification(
            title: __('Report generated'),
            message: __('Your :type report is ready.', [
                'type' => $report->report_type_label,
            ]),
            actionUrl: Route::has('projects.show') && $project
                ? route('projects.show', $project)
                : null,
            organizationId: (int) $organization->id,
        ));

        if (! $project) {
            return;
        }

        $project->loadMissing('manager');

        if ($project->manager && $project->manager->id !== $actor->id) {
            $project->manager->notify(new CrmNotification(
                title: __('Report generated'),
                message: __('A :type report was generated for :project.', [
                    'type' => $report->report_type_label,
                    'project' => $project->name,
                ]),
                actionUrl: Route::has('projects.show') ? route('projects.show', $project) : null,
                organizationId: (int) $organization->id,
            ));
        }
    }
}
