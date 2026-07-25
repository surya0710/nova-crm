<?php

namespace App\Services\Analytics;

use App\Models\Employee;
use App\Models\EmployeeExitProcess;
use App\Models\Lead;
use App\Models\LeaveApplication;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ForecastService;
use App\Services\LeadFollowUpService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AnalyticsInsightsService
{
    protected const REVIEW_NOTE = 'This insight is generated from organizational data and requires human review before acting on it.';

    public function __construct(
        protected ForecastService $forecast,
        protected LeadFollowUpService $followUps,
        protected AnalyticsDomainService $domains,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function build(User $user, Organization $organization): array
    {
        $insights = collect();

        $insights = $insights
            ->merge($this->revenueForecastInsights($user))
            ->merge($this->salesTrendInsights($user))
            ->merge($this->employeeRiskInsights($user))
            ->merge($this->projectRiskInsights($user, $organization))
            ->merge($this->leadScoringInsights($user))
            ->merge($this->suggestedFollowUpInsights($user))
            ->merge($this->executiveSummaryInsights($user, $organization))
            ->merge($this->naturalLanguageInsights($user, $organization));

        return $insights
            ->map(fn (array $insight) => array_merge([
                'requires_review' => true,
                'review_note' => self::REVIEW_NOTE,
            ], $insight))
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function revenueForecastInsights(User $user): Collection
    {
        if (! $user->hasPermission('opportunities.view')) {
            return collect();
        }

        $openStages = config('pipeline.open_stages', ['qualification', 'proposal', 'negotiation']);
        $weighted = (float) Opportunity::query()
            ->whereIn('stage', $openStages)
            ->get(['amount', 'probability'])
            ->sum(function (Opportunity $opportunity) {
                $amount = (float) $opportunity->amount;
                $probability = max(0, min(100, (int) ($opportunity->probability ?? 0)));

                return $amount * ($probability / 100);
            });
        $raw = (float) Opportunity::query()->whereIn('stage', $openStages)->sum('amount');

        return collect([
            [
                'type' => 'revenue_forecast',
                'category' => 'sales',
                'title' => __('Weighted pipeline outlook'),
                'summary' => __('Open pipeline totals :raw with a probability-weighted forecast of :weighted.', [
                    'raw' => number_format($raw, 0),
                    'weighted' => number_format($weighted, 0),
                ]),
                'metrics' => [
                    'open_pipeline' => $raw,
                    'weighted_forecast' => $weighted,
                ],
                'confidence' => 'medium',
            ],
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function salesTrendInsights(User $user): Collection
    {
        if (! $user->hasPermission('leads.view')) {
            return collect();
        }

        $last30 = Lead::query()->where('created_at', '>=', now()->subDays(30))->count();
        $prior30 = Lead::query()
            ->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])
            ->count();

        $delta = $prior30 > 0 ? round((($last30 - $prior30) / $prior30) * 100, 1) : null;
        $direction = $delta === null ? __('stable') : ($delta >= 0 ? __('up :pct%', ['pct' => abs($delta)]) : __('down :pct%', ['pct' => abs($delta)]));

        return collect([
            [
                'type' => 'sales_trends',
                'category' => 'sales',
                'title' => __('Lead intake trend'),
                'summary' => __(':last leads were created in the last 30 days versus :prior in the prior 30 days (:direction).', [
                    'last' => $last30,
                    'prior' => $prior30,
                    'direction' => $direction,
                ]),
                'metrics' => [
                    'last_30_days' => $last30,
                    'prior_30_days' => $prior30,
                    'change_percent' => $delta,
                ],
                'confidence' => 'high',
            ],
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function employeeRiskInsights(User $user): Collection
    {
        if (! $user->hasAnyPermission(['hrms.view', 'hr.dashboard', 'leave.view'])) {
            return collect();
        }

        $insights = collect();
        $pendingLeave = Schema::hasTable('leave_applications')
            ? LeaveApplication::query()->where('status', 'pending')->count()
            : 0;
        $activeExits = Schema::hasTable('employee_exit_processes')
            ? EmployeeExitProcess::query()->whereIn('status', ['in_progress', 'initiated'])->count()
            : 0;

        if ($pendingLeave > 0 || $activeExits > 0) {
            $insights->push([
                'type' => 'employee_risk',
                'category' => 'people',
                'title' => __('Workforce attention signals'),
                'summary' => __('There are :leave pending leave requests and :exits active exit processes that may affect capacity planning.', [
                    'leave' => $pendingLeave,
                    'exits' => $activeExits,
                ]),
                'metrics' => [
                    'pending_leave' => $pendingLeave,
                    'active_exits' => $activeExits,
                ],
                'confidence' => 'medium',
            ]);
        }

        if ($insights->isEmpty()) {
            $insights->push([
                'type' => 'employee_risk',
                'category' => 'people',
                'title' => __('Workforce risk baseline'),
                'summary' => __('No pending leave or active exit processes were detected in the current snapshot.'),
                'metrics' => [
                    'pending_leave' => $pendingLeave,
                    'active_exits' => $activeExits,
                ],
                'confidence' => 'low',
            ]);
        }

        return $insights;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function projectRiskInsights(User $user, Organization $organization): Collection
    {
        if (! $user->hasPermission('projects.view')) {
            return collect();
        }

        $projects = Project::query()
            ->where('organization_id', $organization->id)
            ->where('is_archived', false)
            ->limit(25)
            ->get();

        if ($projects->isEmpty()) {
            return collect([
                [
                    'type' => 'project_risk',
                    'category' => 'delivery',
                    'title' => __('Project risk scan'),
                    'summary' => __('No active projects were found for delivery risk analysis.'),
                    'metrics' => ['at_risk_count' => 0],
                    'confidence' => 'low',
                ],
            ]);
        }

        $atRisk = [];
        foreach ($projects as $project) {
            $forecast = $this->forecast->forProject($project, $user, false);
            $delay = $forecast['likely_delay']['is_likely'] ?? false;
            $overrun = $forecast['budget_overrun']['is_likely'] ?? false;
            $riskScore = (float) ($forecast['risk_forecast']['score'] ?? 0);

            if ($delay || $overrun || $riskScore >= 12) {
                $atRisk[] = [
                    'project_id' => $project->id,
                    'name' => $project->name,
                    'likely_delay' => $delay,
                    'budget_overrun' => $overrun,
                    'risk_score' => $riskScore,
                ];
            }
        }

        return collect([
            [
                'type' => 'project_risk',
                'category' => 'delivery',
                'title' => __('Project risk detection'),
                'summary' => $atRisk === []
                    ? __('No projects currently exceed delay, budget, or risk thresholds in the sampled portfolio.')
                    : __(':count project(s) show elevated delay, budget, or risk signals based on delivery forecasts.', [
                        'count' => count($atRisk),
                    ]),
                'metrics' => [
                    'sample_size' => $projects->count(),
                    'at_risk_count' => count($atRisk),
                    'projects' => array_slice($atRisk, 0, 5),
                ],
                'confidence' => 'medium',
            ],
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function leadScoringInsights(User $user): Collection
    {
        if (! $user->hasPermission('leads.view')) {
            return collect();
        }

        if (Schema::hasColumn('leads', 'score')) {
            $highScore = Lead::query()
                ->whereNotIn('status', ['won', 'lost', 'converted'])
                ->where('score', '>=', 70)
                ->orderByDesc('score')
                ->limit(5)
                ->get(['id', 'name', 'company', 'score']);

            return collect([
                [
                    'type' => 'lead_scoring',
                    'category' => 'sales',
                    'title' => __('High-score open leads'),
                    'summary' => $highScore->isEmpty()
                        ? __('No open leads currently exceed the high-score threshold (70+).')
                        : __(':count open lead(s) exceed a score of 70 and may warrant prioritized follow-up.', [
                            'count' => $highScore->count(),
                        ]),
                    'metrics' => [
                        'threshold' => 70,
                        'leads' => $highScore->map(fn (Lead $lead) => [
                            'id' => $lead->id,
                            'name' => $lead->name,
                            'company' => $lead->company,
                            'score' => $lead->score,
                        ])->all(),
                    ],
                    'confidence' => 'high',
                ],
            ]);
        }

        $highValue = Lead::query()
            ->whereNotIn('status', ['won', 'lost', 'converted'])
            ->when(Schema::hasColumn('leads', 'budget'), fn ($q) => $q->where('budget', '>=', 10000))
            ->orderByDesc(Schema::hasColumn('leads', 'budget') ? 'budget' : 'created_at')
            ->limit(5)
            ->get(['id', 'name', 'company', 'budget', 'priority']);

        return collect([
            [
                'type' => 'lead_scoring',
                'category' => 'sales',
                'title' => __('High-value open leads'),
                'summary' => $highValue->isEmpty()
                    ? __('No high-value open leads were identified using budget and priority heuristics.')
                    : __(':count open lead(s) appear high-value based on budget/priority heuristics (score column unavailable).', [
                        'count' => $highValue->count(),
                    ]),
                'metrics' => [
                    'heuristic' => 'budget_priority',
                    'leads' => $highValue->map(fn (Lead $lead) => [
                        'id' => $lead->id,
                        'name' => $lead->name,
                        'company' => $lead->company,
                        'budget' => $lead->budget ?? null,
                        'priority' => $lead->priority ?? null,
                    ])->all(),
                ],
                'confidence' => 'medium',
            ],
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function suggestedFollowUpInsights(User $user): Collection
    {
        if (! $user->hasPermission('leads.view')) {
            return collect();
        }

        $followUps = $this->followUps->dueForAlertPayloads(5);

        return collect([
            [
                'type' => 'suggested_follow_ups',
                'category' => 'sales',
                'title' => __('Suggested follow-ups'),
                'summary' => $followUps->isEmpty()
                    ? __('No overdue or due-soon lead follow-ups were detected.')
                    : __(':count lead follow-up(s) are due or overdue and may need attention.', [
                        'count' => $followUps->count(),
                    ]),
                'metrics' => [
                    'follow_ups' => $followUps->values()->all(),
                ],
                'confidence' => 'high',
            ],
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function executiveSummaryInsights(User $user, Organization $organization): Collection
    {
        $exec = $this->domains->executive($user, $organization);
        $pipeline = (float) ($exec['sales_pipeline']['open_value'] ?? 0);
        $outstanding = (float) ($exec['financial_kpis']['outstanding_ar'] ?? 0);
        $headcount = (int) ($exec['employee_growth']['active'] ?? 0);
        $atRisk = (int) ($exec['project_health']['kpis']['at_risk_count'] ?? 0);

        $text = __('Executive snapshot for :org: pipeline :pipeline, outstanding AR :ar, headcount :hc, and :risk at-risk projects.', [
            'org' => $organization->name,
            'pipeline' => number_format($pipeline, 0),
            'ar' => number_format($outstanding, 0),
            'hc' => $headcount,
            'risk' => $atRisk,
        ]);

        return collect([
            [
                'type' => 'executive_summary',
                'category' => 'executive',
                'title' => __('Executive summary'),
                'summary' => $text,
                'metrics' => [
                    'pipeline_value' => $pipeline,
                    'outstanding_ar' => $outstanding,
                    'headcount' => $headcount,
                    'at_risk_projects' => $atRisk,
                ],
                'confidence' => 'medium',
            ],
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function naturalLanguageInsights(User $user, Organization $organization): Collection
    {
        $lines = collect();

        if ($user->hasPermission('opportunities.view')) {
            $open = Opportunity::query()->whereIn('stage', config('pipeline.open_stages', []))->count();
            $lines->push(__('Sales: :count open opportunities remain in the pipeline.', ['count' => $open]));
        }

        if ($user->hasPermission('projects.view')) {
            $active = Project::query()->where('organization_id', $organization->id)->where('is_archived', false)->count();
            $lines->push(__('Delivery: :count active projects are in flight.', ['count' => $active]));
        }

        if ($user->hasAnyPermission(['hrms.view', 'hr.dashboard']) && Schema::hasTable('employees')) {
            $activeEmployees = Employee::query()
                ->whereIn('status', config('hrms.leave_applicable_employee_statuses', []))
                ->count();
            $lines->push(__('People: :count employees are currently active.', ['count' => $activeEmployees]));
        }

        if ($lines->isEmpty()) {
            return collect();
        }

        return collect([
            [
                'type' => 'natural_language',
                'category' => 'summary',
                'title' => __('Organization pulse'),
                'summary' => $lines->implode(' '),
                'metrics' => ['lines' => $lines->values()->all()],
                'confidence' => 'medium',
            ],
        ]);
    }
}
