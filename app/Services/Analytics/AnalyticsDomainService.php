<?php

namespace App\Services\Analytics;

use App\Models\AttendanceRecord;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeExitProcess;
use App\Models\Invoice;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\Lead;
use App\Models\LeaveApplication;
use App\Models\OfferLetter;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PayrollRun;
use App\Models\PerformanceReview;
use App\Models\Project;
use App\Models\ResourceAllocation;
use App\Models\User;
use App\Services\ExecutiveDashboardService;
use App\Services\ForecastService;
use App\Services\ReportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class AnalyticsDomainService
{
    public function __construct(
        protected ReportService $reports,
        protected ExecutiveDashboardService $executive,
        protected ForecastService $forecast,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function executive(User $user, Organization $organization): array
    {
        $report = $user->hasPermission('reports.view')
            ? $this->reports->compile($organization)
            : [];

        $exec = $user->hasPermission('projects.view')
            ? $this->executive->forOrganization($organization, $user)
            : [];

        return [
            'revenue' => [
                'collected' => (float) ($report['revenue_collected'] ?? 0),
                'outstanding' => (float) ($report['outstanding_amount'] ?? 0),
                'monthly' => $report['monthly_revenue'] ?? collect(),
                'href' => $this->routeIfExists('reports.finance'),
            ],
            'sales_pipeline' => [
                'open_value' => (float) ($report['open_pipeline_value'] ?? 0),
                'by_stage' => $report['opportunity_by_stage'] ?? collect(),
                'href' => $this->routeIfExists('analytics.crm') ?? $this->routeIfExists('pipeline.index'),
            ],
            'lead_funnel' => $this->leadFunnel($user),
            'customer_growth' => $this->customerGrowth($user),
            'employee_growth' => $this->employeeGrowth($user),
            'project_health' => [
                'summary' => $exec['portfolio_health'] ?? [],
                'kpis' => $exec['kpis'] ?? [],
                'at_risk' => $exec['at_risk_projects'] ?? [],
                'href' => $this->routeIfExists('analytics.projects')
                    ?? $this->routeIfExists('projects.executive'),
            ],
            'recruitment_metrics' => $this->recruitmentMetrics($user),
            'financial_kpis' => [
                'outstanding_ar' => (float) ($report['outstanding_amount'] ?? 0),
                'outstanding_count' => (int) ($report['outstanding_count'] ?? 0),
                'invoice_counts' => $report['invoice_counts'] ?? collect(),
                'payments_by_method' => $report['payments_by_method'] ?? collect(),
                'href' => $this->routeIfExists('reports.finance'),
            ],
            'href' => $this->routeIfExists('analytics.executive'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function crm(User $user, Organization $organization): array
    {
        $report = $user->hasPermission('reports.view')
            ? $this->reports->compile($organization)
            : [];

        return [
            'lead_sources' => $this->leadSources($user),
            'pipeline_conversion' => $this->pipelineConversion($user),
            'sales_performance' => [
                'top_performers' => $report['top_performers'] ?? collect(),
                'conversion_rate' => $report['conversion_rate'] ?? null,
                'href' => $this->routeIfExists('reports.index'),
            ],
            'customer_acquisition' => $this->customerAcquisition($user),
            'revenue_forecast' => [
                'weighted_value' => $this->weightedPipelineValue(),
                'open_pipeline_value' => (float) ($report['open_pipeline_value'] ?? 0),
            ],
            'win_loss' => $this->winLossAnalysis($user),
            'href' => $this->routeIfExists('analytics.crm') ?? $this->routeIfExists('reports.index'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function projects(User $user, Organization $organization): array
    {
        $exec = $user->hasPermission('projects.view')
            ? $this->executive->forOrganization($organization, $user)
            : [];

        $activeProjects = $user->hasPermission('projects.view')
            ? Project::query()
                ->where('organization_id', $organization->id)
                ->where('is_archived', false)
                ->limit(100)
                ->get()
            : collect();

        $deliveryTrends = [];
        foreach ($activeProjects->take(20) as $project) {
            $delay = $this->forecast->likelyDelay($project);
            $deliveryTrends[] = [
                'project_id' => $project->id,
                'name' => $project->name,
                'likely_delay' => $delay['is_likely'] ?? false,
                'estimated_completion' => $this->forecast->estimatedCompletion($project)?->toDateString(),
            ];
        }

        return [
            'progress' => [
                'average_completion_percentage' => $exec['progress']['average_completion_percentage'] ?? 0,
                'active_project_count' => $exec['progress']['active_project_count'] ?? 0,
            ],
            'resource_utilization' => $this->resourceUtilization($organization, $user),
            'budget_vs_actual' => $exec['budget_status'] ?? [],
            'milestones' => [
                'upcoming' => $exec['upcoming_milestones'] ?? [],
            ],
            'portfolio_health' => [
                'summary' => $exec['portfolio_health'] ?? [],
                'portfolios' => $exec['portfolios'] ?? [],
            ],
            'delivery_trends' => $deliveryTrends,
            'delivery_forecast' => $exec['delivery_forecast'] ?? [],
            'risk_overview' => $exec['risk_overview'] ?? [],
            'href' => $this->routeIfExists('analytics.projects')
                ?? $this->routeIfExists('projects.executive')
                ?? $this->routeIfExists('projects.reports.hub'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function hr(User $user, Organization $organization): array
    {
        return [
            'headcount' => $this->headcountMetrics($user),
            'attendance_trends' => $this->attendanceTrends($user),
            'leave_trends' => $this->leaveTrends($user),
            'recruitment_funnel' => $this->recruitmentFunnel($user),
            'performance_distribution' => $this->performanceDistribution($user),
            'payroll_summary' => $this->payrollSummary($user),
            'attrition' => $this->attritionMetrics($user),
            'workforce_capacity' => $this->workforceCapacity($user, $organization),
            'href' => $this->routeIfExists('analytics.hr')
                ?? $this->routeIfExists('hrms.dashboard')
                ?? $this->routeIfExists('hrms.recruitment.analytics'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function leadFunnel(User $user): array
    {
        if (! $user->hasPermission('leads.view')) {
            return ['counts' => [], 'href' => null];
        }

        $counts = Lead::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($count) => (int) $count)
            ->all();

        return [
            'counts' => $counts,
            'total' => array_sum($counts),
            'href' => $this->routeIfExists('leads.index'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function customerGrowth(User $user): array
    {
        if (! $user->hasPermission('customers.view') || ! Schema::hasTable('customers')) {
            return ['total' => 0, 'recent_30d' => 0, 'href' => null];
        }

        return [
            'total' => Customer::query()->count(),
            'recent_30d' => Customer::query()->where('created_at', '>=', now()->subDays(30))->count(),
            'prior_30d' => Customer::query()
                ->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])
                ->count(),
            'href' => $this->routeIfExists('customers.index'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function employeeGrowth(User $user): array
    {
        if (! $user->hasAnyPermission(['hrms.view', 'hr.dashboard']) || ! Schema::hasTable('employees')) {
            return ['active' => 0, 'new_joiners_30d' => 0, 'href' => null];
        }

        $activeStatuses = config('hrms.leave_applicable_employee_statuses', []);

        return [
            'active' => Employee::query()->whereIn('status', $activeStatuses)->count(),
            'new_joiners_30d' => Employee::query()
                ->whereIn('status', $activeStatuses)
                ->where('joining_date', '>=', now()->subDays(30)->toDateString())
                ->count(),
            'href' => $this->routeIfExists('hrms.employees.index'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function recruitmentMetrics(User $user): array
    {
        if (! $user->hasPermission('recruitment.view')) {
            return ['href' => $this->routeIfExists('hrms.recruitment.analytics')];
        }

        return array_merge($this->recruitmentFunnel($user), [
            'href' => $this->routeIfExists('hrms.recruitment.analytics')
                ?? $this->routeIfExists('hrms.recruitment.executive'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function leadSources(User $user): array
    {
        if (! $user->hasPermission('leads.view') || ! Schema::hasColumn('leads', 'source')) {
            return ['distribution' => [], 'href' => null];
        }

        $distribution = Lead::query()
            ->selectRaw('COALESCE(source, ?) as source_label, COUNT(*) as total', [__('Unknown')])
            ->groupBy('source_label')
            ->orderByDesc('total')
            ->pluck('total', 'source_label')
            ->map(fn ($count) => (int) $count)
            ->all();

        return [
            'distribution' => $distribution,
            'href' => $this->routeIfExists('leads.index'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function pipelineConversion(User $user): array
    {
        if (! $user->hasPermission('opportunities.view')) {
            return ['stages' => [], 'href' => null];
        }

        $stages = Opportunity::query()
            ->select('stage', DB::raw('count(*) as total'), DB::raw('COALESCE(SUM(amount), 0) as value'))
            ->groupBy('stage')
            ->orderBy('stage')
            ->get()
            ->map(fn ($row) => [
                'stage' => (string) $row->stage,
                'count' => (int) $row->total,
                'value' => (float) $row->value,
            ])
            ->all();

        return [
            'stages' => $stages,
            'href' => $this->routeIfExists('pipeline.index'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function customerAcquisition(User $user): array
    {
        if (! $user->hasPermission('customers.view') || ! Schema::hasTable('customers')) {
            return ['total' => 0, 'with_lead' => 0, 'href' => null];
        }

        return [
            'total' => Customer::query()->count(),
            'with_lead' => Schema::hasColumn('customers', 'lead_id')
                ? Customer::query()->whereNotNull('lead_id')->count()
                : 0,
            'recent_30d' => Customer::query()->where('created_at', '>=', now()->subDays(30))->count(),
            'href' => $this->routeIfExists('customers.index'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function winLossAnalysis(User $user): array
    {
        if (! $user->hasPermission('opportunities.view')) {
            return ['won' => 0, 'lost' => 0, 'win_rate' => null, 'href' => null];
        }

        $won = Opportunity::query()->where('stage', 'closed_won')->count();
        $lost = Opportunity::query()->where('stage', 'closed_lost')->count();
        $closed = $won + $lost;

        return [
            'won' => $won,
            'lost' => $lost,
            'win_rate' => $closed > 0 ? round(($won / $closed) * 100, 1) : null,
            'won_value' => (float) Opportunity::query()->where('stage', 'closed_won')->sum('amount'),
            'lost_value' => (float) Opportunity::query()->where('stage', 'closed_lost')->sum('amount'),
            'href' => $this->routeIfExists('pipeline.index'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function resourceUtilization(Organization $organization, User $user): array
    {
        if (! $user->hasPermission('projects.view') || ! Schema::hasTable('resource_allocations')) {
            return ['available' => false, 'average_allocation' => null, 'over_allocated' => 0];
        }

        $allocations = ResourceAllocation::query()
            ->where('organization_id', $organization->id)
            ->where(function ($query) {
                $query->whereNull('planned_end_date')
                    ->orWhereDate('planned_end_date', '>=', now()->toDateString());
            })
            ->get(['employee_id', 'allocation_percentage']);

        if ($allocations->isEmpty()) {
            return ['available' => true, 'average_allocation' => 0, 'over_allocated' => 0];
        }

        $byEmployee = $allocations->groupBy('employee_id')->map(
            fn ($rows) => (int) $rows->sum('allocation_percentage')
        );

        return [
            'available' => true,
            'average_allocation' => round((float) $byEmployee->avg(), 1),
            'over_allocated' => $byEmployee->filter(fn ($pct) => $pct > 100)->count(),
            'allocated_employees' => $byEmployee->count(),
            'href' => $this->routeIfExists('resources.planner'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function headcountMetrics(User $user): array
    {
        if (! $user->hasAnyPermission(['hrms.view', 'hr.dashboard']) || ! Schema::hasTable('employees')) {
            return ['active' => null, 'total' => null, 'href' => null];
        }

        $activeStatuses = config('hrms.leave_applicable_employee_statuses', []);

        return [
            'active' => Employee::query()->whereIn('status', $activeStatuses)->count(),
            'total' => Employee::query()->count(),
            'href' => $this->routeIfExists('hrms.directory.index'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function attendanceTrends(User $user): array
    {
        if (! $user->hasPermission('attendance.view') || ! Schema::hasTable('attendance_records')) {
            return ['daily' => [], 'href' => null];
        }

        $from = now()->subDays(13)->toDateString();
        $daily = AttendanceRecord::query()
            ->whereDate('attendance_date', '>=', $from)
            ->selectRaw('attendance_date, status, COUNT(*) as total')
            ->groupBy('attendance_date', 'status')
            ->orderBy('attendance_date')
            ->get()
            ->groupBy(fn ($row) => (string) $row->attendance_date)
            ->map(fn ($rows) => $rows->pluck('total', 'status')->map(fn ($count) => (int) $count)->all())
            ->all();

        return [
            'daily' => $daily,
            'href' => $this->routeIfExists('hrms.attendance.summary'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function leaveTrends(User $user): array
    {
        if (! $user->hasAnyPermission(['hrms.view', 'leave.view', 'hr.dashboard']) || ! Schema::hasTable('leave_applications')) {
            return ['pending' => null, 'approved_30d' => null, 'href' => null];
        }

        return [
            'pending' => LeaveApplication::query()->where('status', 'pending')->count(),
            'approved_30d' => LeaveApplication::query()
                ->where('status', 'approved')
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
            'on_leave_today' => LeaveApplication::query()
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->count(),
            'href' => $this->routeIfExists('hrms.leave.dashboard'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function recruitmentFunnel(User $user): array
    {
        if (! $user->hasPermission('recruitment.view')) {
            return ['open_openings' => null, 'stages' => [], 'href' => null];
        }

        $stages = Schema::hasTable('job_applications')
            ? JobApplication::query()
                ->select('stage', DB::raw('count(*) as total'))
                ->groupBy('stage')
                ->pluck('total', 'stage')
                ->map(fn ($count) => (int) $count)
                ->all()
            : [];

        return [
            'open_openings' => Schema::hasTable('job_openings')
                ? JobOpening::query()->whereIn('status', ['published', 'paused'])->count()
                : 0,
            'applications' => Schema::hasTable('job_applications')
                ? JobApplication::query()->count()
                : 0,
            'offers_pending' => Schema::hasTable('offer_letters')
                ? OfferLetter::query()->whereIn('status', ['draft', 'pending_approval', 'approved', 'sent'])->count()
                : 0,
            'stages' => $stages,
            'href' => $this->routeIfExists('hrms.recruitment.dashboard'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function performanceDistribution(User $user): array
    {
        if (! $user->hasAnyPermission(['performance.view', 'performance.review.view']) || ! Schema::hasTable('performance_reviews')) {
            return ['by_status' => [], 'href' => null];
        }

        $byStatus = PerformanceReview::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($count) => (int) $count)
            ->all();

        return [
            'by_status' => $byStatus,
            'href' => $this->routeIfExists('hrms.performance.index'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function payrollSummary(User $user): array
    {
        if (! $user->hasPermission('payroll.view') || ! Schema::hasTable('payroll_runs')) {
            return ['latest_run' => null, 'href' => null];
        }

        $run = PayrollRun::query()->with('period')->latest('id')->first();

        return [
            'latest_run' => $run ? [
                'id' => $run->id,
                'status' => $run->status,
                'period' => $run->period?->name,
            ] : null,
            'href' => $this->routeIfExists('hrms.payroll.reports.index')
                ?? $this->routeIfExists('hrms.payroll.index'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function attritionMetrics(User $user): array
    {
        if (! $user->hasAnyPermission(['hrms.view', 'hr.dashboard']) || ! Schema::hasTable('employees')) {
            return ['trailing_12m_exits' => null, 'rate_percent' => null, 'href' => null];
        }

        $activeStatuses = config('hrms.leave_applicable_employee_statuses', []);
        $headcount = Employee::query()->whereIn('status', $activeStatuses)->count();
        $exits = 0;

        if (Schema::hasTable('employee_exit_processes')) {
            $exits = EmployeeExitProcess::query()
                ->where('created_at', '>=', now()->subMonths(12))
                ->whereIn('status', ['completed', 'in_progress'])
                ->count();
        } elseif (Schema::hasColumn('employees', 'exit_date')) {
            $exits = Employee::query()
                ->whereNotNull('exit_date')
                ->where('exit_date', '>=', now()->subMonths(12)->toDateString())
                ->count();
        }

        return [
            'trailing_12m_exits' => $exits,
            'rate_percent' => $headcount > 0 ? round(($exits / $headcount) * 100, 1) : null,
            'href' => $this->routeIfExists('hrms.employees.index'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function workforceCapacity(User $user, Organization $organization): array
    {
        $capacity = [
            'headcount' => null,
            'on_leave_today' => null,
            'pending_leave' => null,
            'open_roles' => null,
        ];

        if ($user->hasAnyPermission(['hrms.view', 'hr.dashboard']) && Schema::hasTable('employees')) {
            $capacity['headcount'] = Employee::query()
                ->whereIn('status', config('hrms.leave_applicable_employee_statuses', []))
                ->count();
        }

        if (Schema::hasTable('leave_applications')) {
            $capacity['on_leave_today'] = LeaveApplication::query()
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->count();
            $capacity['pending_leave'] = LeaveApplication::query()->where('status', 'pending')->count();
        }

        if ($user->hasPermission('recruitment.view') && Schema::hasTable('job_openings')) {
            $capacity['open_roles'] = JobOpening::query()->whereIn('status', ['published', 'paused'])->count();
        }

        if ($user->hasPermission('projects.view') && Schema::hasTable('resource_allocations')) {
            $utilization = $this->resourceUtilization($organization, $user);
            $capacity['avg_project_allocation'] = $utilization['average_allocation'] ?? null;
            $capacity['over_allocated'] = $utilization['over_allocated'] ?? 0;
        }

        return $capacity;
    }

    protected function weightedPipelineValue(): float
    {
        $openStages = config('pipeline.open_stages', ['qualification', 'proposal', 'negotiation']);

        return (float) Opportunity::query()
            ->whereIn('stage', $openStages)
            ->get(['amount', 'probability'])
            ->sum(function (Opportunity $opportunity) {
                $amount = (float) $opportunity->amount;
                $probability = max(0, min(100, (int) ($opportunity->probability ?? 0)));

                return $amount * ($probability / 100);
            });
    }

    protected function routeIfExists(string $name, mixed $parameters = []): ?string
    {
        return Route::has($name) ? route($name, $parameters) : null;
    }
}
