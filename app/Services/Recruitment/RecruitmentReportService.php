<?php

namespace App\Services\Recruitment;

use App\Models\RecruitmentSavedReport;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class RecruitmentReportService
{
    public function __construct(
        protected RecruitmentDashboardService $dashboard,
        protected RecruitmentAnalyticsService $analytics,
        protected RecruitmentKpiService $kpis,
        protected RecruitmentTrendService $trends,
        protected AuditLogger $auditLogger,
        protected TenantContext $tenant,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function compile(string $reportType, array $filters = [], ?User $actor = null): array
    {
        $types = config('hrms.recruitment.report_types', []);
        if (! array_key_exists($reportType, $types)) {
            throw ValidationException::withMessages([
                'report_type' => __('Invalid report type.'),
            ]);
        }

        $payload = match ($reportType) {
            'recruitment_summary' => [
                'kpis' => $this->kpis->executiveKpis($filters, $actor),
                'time_metrics' => $this->kpis->timeMetrics($filters, $actor),
                'funnel' => $this->analytics->funnel($filters, $actor),
            ],
            'recruiter_performance' => [
                'rows' => $this->analytics->recruiterPerformance($filters, $actor),
            ],
            'hiring_manager_performance' => [
                'metrics' => $this->dashboard->hiringManagerMetrics($filters, $actor),
            ],
            'department_hiring' => [
                'metrics' => $this->analytics->departmentAnalytics($filters, $actor),
            ],
            'open_positions' => [
                'openings' => $this->analytics->jobOpeningAnalytics($filters, $actor),
                'vacancy_aging' => $this->analytics->departmentAnalytics($filters, $actor)['vacancy_aging'] ?? [],
            ],
            'pipeline' => [
                'funnel' => $this->analytics->funnel($filters, $actor),
                'candidate_analytics' => $this->analytics->candidateAnalytics($filters, $actor),
            ],
            'offer' => [
                'kpis' => $this->kpis->executiveKpis($filters, $actor),
                'time_metrics' => $this->kpis->timeMetrics($filters, $actor),
            ],
            'source' => [
                'rows' => $this->analytics->sourceEffectiveness($filters, $actor),
                'trends' => $this->trends->trends($filters, $actor)['source_trends'] ?? [],
            ],
            'vacancy_aging' => [
                'rows' => $this->analytics->departmentAnalytics($filters, $actor)['vacancy_aging'] ?? [],
            ],
            default => [],
        };

        return [
            'report_type' => $reportType,
            'report_label' => $types[$reportType],
            'filters' => collect($filters)->except('_department_ids')->all(),
            'generated_at' => now()->toIso8601String(),
            'data' => $payload,
        ];
    }

    /**
     * @return list<array{type: string, label: string}>
     */
    public function availableReports(): array
    {
        return collect(config('hrms.recruitment.report_types', []))
            ->map(fn (string $label, string $type) => ['type' => $type, 'label' => $label])
            ->values()
            ->all();
    }

    public function listSavedReports(User $actor, bool $includeShared = true): LengthAwarePaginator
    {
        return RecruitmentSavedReport::query()
            ->with('user:id,name')
            ->where(function ($q) use ($actor, $includeShared) {
                $q->where('user_id', $actor->id);
                if ($includeShared) {
                    $q->orWhere('is_shared', true);
                }
            })
            ->latest()
            ->paginate(20);
    }

    /**
     * @param  array{report_name: string, report_type: string, filters_json?: array<string, mixed>|null, is_shared?: bool}  $data
     */
    public function saveReport(array $data, User $actor): RecruitmentSavedReport
    {
        $report = RecruitmentSavedReport::query()->create([
            'organization_id' => $this->tenant->id(),
            'user_id' => $actor->id,
            'report_name' => $data['report_name'],
            'report_type' => $data['report_type'],
            'filters_json' => $data['filters_json'] ?? [],
            'is_shared' => (bool) ($data['is_shared'] ?? false),
        ]);

        $this->auditLogger->log($report, 'recruitment_report_created', [
            'report_type' => $report->report_type,
            'report_name' => $report->report_name,
        ], $actor);

        return $report;
    }

    /**
     * @param  array{report_name?: string, report_type?: string, filters_json?: array<string, mixed>|null, is_shared?: bool}  $data
     */
    public function updateReport(RecruitmentSavedReport $report, array $data, User $actor): RecruitmentSavedReport
    {
        $wasShared = $report->is_shared;

        $report->fill([
            'report_name' => $data['report_name'] ?? $report->report_name,
            'report_type' => $data['report_type'] ?? $report->report_type,
            'filters_json' => array_key_exists('filters_json', $data) ? $data['filters_json'] : $report->filters_json,
            'is_shared' => array_key_exists('is_shared', $data) ? (bool) $data['is_shared'] : $report->is_shared,
        ]);
        $report->save();

        if ($wasShared !== $report->is_shared) {
            $this->auditLogger->log($report, 'recruitment_report_shared', [
                'is_shared' => $report->is_shared,
            ], $actor);
        }

        return $report->fresh();
    }

    public function deleteReport(RecruitmentSavedReport $report, User $actor): void
    {
        $this->auditLogger->log($report, 'recruitment_report_deleted', [
            'report_type' => $report->report_type,
            'report_name' => $report->report_name,
        ], $actor);

        $report->delete();
    }

    public function shareReport(RecruitmentSavedReport $report, bool $shared, User $actor): RecruitmentSavedReport
    {
        $report->update(['is_shared' => $shared]);

        $this->auditLogger->log($report, 'recruitment_report_shared', [
            'is_shared' => $shared,
        ], $actor);

        return $report->fresh();
    }
}
