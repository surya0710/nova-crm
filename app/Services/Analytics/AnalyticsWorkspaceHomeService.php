<?php

namespace App\Services\Analytics;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Models\UserUiPreference;
use App\Services\ExecutiveDashboardService;
use App\Services\ReportService;
use App\Services\TenantContext;
use App\Services\Workspace\CachesWorkspaceHome;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class AnalyticsWorkspaceHomeService
{
    use CachesWorkspaceHome;

    public function __construct(
        protected TenantContext $tenant,
        protected ReportService $reports,
        protected ExecutiveDashboardService $executive,
        protected AnalyticsDomainService $domains,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        return $this->rememberHome('analytics', $user, fn () => $this->buildUncached($user));
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildUncached(User $user): array
    {
        $organization = $this->tenant->get();
        $prefs = UserUiPreference::query()
            ->where('user_id', $user->id)
            ->where('organization_id', $organization?->id)
            ->first();

        $defaultWidgets = [
            'pipeline_vs_target', 'project_health', 'headcount', 'outstanding_ar',
            'domain_sales', 'domain_delivery', 'domain_people', 'domain_finance', 'domain_audit',
            'attention', 'quick_actions', 'recent_activity',
        ];

        return [
            'kpis' => $this->kpis($user, $organization),
            'domainCards' => $this->domainCards($user, $organization),
            'attention' => $this->attention($user, $organization),
            'quickActions' => $this->quickActions($user),
            'recentActivity' => $this->recentActivity($user),
            'widgetLayout' => $prefs?->dashboard_layout['analytics'] ?? [
                'widgets' => $defaultWidgets,
                'hidden' => [],
            ],
            'availableWidgets' => $this->availableWidgets(),
        ];
    }

    /** @return list<array{key: string, label: string}> */
    public function availableWidgets(): array
    {
        return [
            ['key' => 'pipeline_vs_target', 'label' => __('Pipeline vs Target')],
            ['key' => 'project_health', 'label' => __('Project Health')],
            ['key' => 'headcount', 'label' => __('Headcount')],
            ['key' => 'outstanding_ar', 'label' => __('Outstanding AR')],
            ['key' => 'domain_sales', 'label' => __('Sales Analytics')],
            ['key' => 'domain_delivery', 'label' => __('Delivery Analytics')],
            ['key' => 'domain_people', 'label' => __('People Analytics')],
            ['key' => 'domain_finance', 'label' => __('Finance Analytics')],
            ['key' => 'domain_audit', 'label' => __('Audit Analytics')],
            ['key' => 'attention', 'label' => __('Needs Attention')],
            ['key' => 'quick_actions', 'label' => __('Quick Actions')],
            ['key' => 'recent_activity', 'label' => __('Recent Activity')],
        ];
    }

    /**
     * @return array<int, array{label: string, value: string|int|float, hint?: string|null, key?: string}>
     */
    protected function kpis(User $user, ?Organization $organization): array
    {
        $kpis = [];

        if ($user->hasPermission('opportunities.view')) {
            $pipelineValue = (float) Opportunity::query()
                ->whereIn('stage', $this->openStages())
                ->sum('amount');
            $target = $this->pipelineTarget($organization);
            $vsTarget = $target > 0
                ? round(($pipelineValue / $target) * 100, 1).'%'
                : __('—');
            $kpis[] = [
                'key' => 'pipeline_vs_target',
                'label' => __('Pipeline vs target'),
                'value' => number_format($pipelineValue, 0),
                'hint' => $target > 0
                    ? __(':pct of :target target', ['pct' => $vsTarget, 'target' => number_format($target, 0)])
                    : __('Open pipeline value'),
            ];
        }

        if ($user->hasPermission('projects.view') && $organization) {
            $rollup = $this->projectHealthRollup($user, $organization);
            $kpis[] = [
                'key' => 'project_health',
                'label' => __('Project health'),
                'value' => $rollup['label'],
                'hint' => $rollup['hint'],
            ];
        }

        if ($user->hasAnyPermission(['hrms.view', 'hr.dashboard']) && Schema::hasTable('employees')) {
            $headcount = Employee::query()
                ->whereIn('status', config('hrms.leave_applicable_employee_statuses', []))
                ->count();
            $kpis[] = [
                'key' => 'headcount',
                'label' => __('Headcount'),
                'value' => $headcount,
                'hint' => __('Active employees'),
            ];
        }

        if ($user->hasPermission('invoices.view') && $this->invoiceBalanceColumnsExist()) {
            $outstanding = (float) Invoice::query()
                ->whereNotIn('status', ['cancelled', 'draft'])
                ->selectRaw('COALESCE(SUM(total - amount_paid), 0) as outstanding')
                ->value('outstanding');
            $kpis[] = [
                'key' => 'outstanding_ar',
                'label' => __('Outstanding AR'),
                'value' => number_format($outstanding, 0),
                'hint' => __('Invoices balance due'),
            ];
        }

        if ($user->hasPermission('audit.view') && Schema::hasTable('audit_logs')) {
            $kpis[] = [
                'key' => 'audit_anomalies',
                'label' => __('Audit anomalies'),
                'value' => $this->auditAnomaliesCount(),
                'hint' => __('Flagged events (7 days)'),
            ];
        }

        return array_slice($kpis, 0, 6);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function domainCards(User $user, ?Organization $organization): array
    {
        if (! $organization) {
            return [];
        }

        $cards = [];

        if ($user->hasAnyPermission(['reports.view', 'opportunities.view', 'leads.view'])) {
            $report = $user->hasPermission('reports.view')
                ? $this->reports->compile($organization)
                : null;
            $cards[] = [
                'key' => 'sales',
                'label' => __('Sales'),
                'metrics' => [
                    ['label' => __('Open pipeline'), 'value' => number_format((float) ($report['open_pipeline_value'] ?? 0), 0)],
                    ['label' => __('Open leads'), 'value' => $user->hasPermission('leads.view')
                        ? Lead::query()->whereNotIn('status', ['won', 'lost', 'converted'])->count()
                        : '—'],
                    ['label' => __('Win rate'), 'value' => isset($report['conversion_rate']) ? $report['conversion_rate'].'%' : '—'],
                ],
                'href' => $this->routeIfExists('analytics.crm') ?? $this->routeIfExists('reports.index'),
            ];
        }

        if ($user->hasPermission('projects.view')) {
            $exec = $this->executive->forOrganization($organization, $user);
            $cards[] = [
                'key' => 'delivery',
                'label' => __('Delivery'),
                'metrics' => [
                    ['label' => __('Active projects'), 'value' => $exec['kpis']['active_projects'] ?? 0],
                    ['label' => __('At risk'), 'value' => $exec['kpis']['at_risk_count'] ?? 0],
                    ['label' => __('Avg completion'), 'value' => ($exec['kpis']['average_completion_percentage'] ?? 0).'%'],
                ],
                'href' => $this->routeIfExists('analytics.projects')
                    ?? $this->routeIfExists('projects.executive')
                    ?? $this->routeIfExists('projects.reports.hub'),
            ];
        }

        if ($user->hasAnyPermission(['hrms.view', 'hr.dashboard', 'recruitment.view'])) {
            $hr = $this->domains->hr($user, $organization);
            $cards[] = [
                'key' => 'people',
                'label' => __('People'),
                'metrics' => [
                    ['label' => __('Headcount'), 'value' => $hr['headcount']['active'] ?? '—'],
                    ['label' => __('Pending leave'), 'value' => $hr['leave_trends']['pending'] ?? '—'],
                    ['label' => __('Open roles'), 'value' => $hr['recruitment_funnel']['open_openings'] ?? '—'],
                ],
                'href' => $this->routeIfExists('analytics.hr')
                    ?? $this->routeIfExists('hrms.dashboard')
                    ?? $this->routeIfExists('hrms.recruitment.analytics'),
            ];
        }

        if ($user->hasAnyPermission(['reports.view', 'invoices.view', 'finance.view'])) {
            $report = $user->hasPermission('reports.view')
                ? $this->reports->compile($organization)
                : null;
            $cards[] = [
                'key' => 'finance',
                'label' => __('Finance'),
                'metrics' => [
                    ['label' => __('Outstanding AR'), 'value' => number_format((float) ($report['outstanding_amount'] ?? 0), 0)],
                    ['label' => __('Outstanding count'), 'value' => $report['outstanding_count'] ?? '—'],
                    ['label' => __('Revenue collected'), 'value' => number_format((float) ($report['revenue_collected'] ?? 0), 0)],
                ],
                'href' => $this->routeIfExists('analytics.reports.index')
                    ?? $this->routeIfExists('reports.finance')
                    ?? $this->routeIfExists('reports.index'),
            ];
        }

        if ($user->hasPermission('audit.view')) {
            $cards[] = [
                'key' => 'audit',
                'label' => __('Audit'),
                'metrics' => [
                    ['label' => __('Anomalies (7d)'), 'value' => Schema::hasTable('audit_logs') ? $this->auditAnomaliesCount() : '—'],
                    ['label' => __('Recent events'), 'value' => Schema::hasTable('audit_logs')
                        ? AuditLog::query()->where('created_at', '>=', now()->subDays(7))->count()
                        : '—'],
                ],
                'href' => $this->routeIfExists('audit-logs.index'),
            ];
        }

        return $cards;
    }

    /**
     * @return Collection<int, array{title: string, subtitle?: string|null, href?: string|null, badge?: string|null}>
     */
    protected function attention(User $user, ?Organization $organization): Collection
    {
        $items = collect();

        if ($user->hasPermission('opportunities.view')) {
            $openDeals = Opportunity::query()->whereIn('stage', $this->openStages())->count();
            $target = $this->pipelineTarget($organization);
            if ($target > 0 && $openDeals > 0) {
                $pipelineValue = (float) Opportunity::query()->whereIn('stage', $this->openStages())->sum('amount');
                if ($pipelineValue < ($target * 0.5)) {
                    $items->push([
                        'title' => __('Pipeline below 50% of target'),
                        'subtitle' => __('Review open opportunities and follow-ups'),
                        'href' => $this->routeIfExists('analytics.crm') ?? $this->routeIfExists('pipeline.index'),
                        'badge' => __('Sales'),
                    ]);
                }
            }
        }

        if ($user->hasPermission('projects.view') && $organization) {
            $exec = $this->executive->forOrganization($organization, $user);
            $atRisk = (int) ($exec['kpis']['at_risk_count'] ?? 0);
            if ($atRisk > 0) {
                $items->push([
                    'title' => __(':count at-risk projects', ['count' => $atRisk]),
                    'subtitle' => __('Delivery health needs review'),
                    'href' => $this->routeIfExists('projects.executive'),
                    'badge' => __('Delivery'),
                ]);
            }
        }

        if ($user->hasPermission('invoices.view') && Schema::hasColumn('invoices', 'due_date')) {
            $overdue = Invoice::query()
                ->whereNotIn('status', ['cancelled', 'draft', 'paid'])
                ->whereDate('due_date', '<', now()->toDateString())
                ->count();
            if ($overdue > 0) {
                $items->push([
                    'title' => __(':count overdue invoices', ['count' => $overdue]),
                    'subtitle' => __('Outstanding receivables'),
                    'href' => $this->routeIfExists('reports.finance'),
                    'badge' => __('Finance'),
                ]);
            }
        }

        if ($user->hasPermission('audit.view') && Schema::hasTable('audit_logs')) {
            $anomalies = $this->auditAnomaliesCount();
            if ($anomalies > 0) {
                $items->push([
                    'title' => __(':count audit anomalies', ['count' => $anomalies]),
                    'subtitle' => __('Review flagged audit events'),
                    'href' => $this->routeIfExists('audit-logs.index'),
                    'badge' => __('Audit'),
                ]);
            }
        }

        return $items->take(8);
    }

    /**
     * @return array<int, array{label: string, href: string, variant?: string}>
     */
    protected function quickActions(User $user): array
    {
        $actions = [];

        if ($user->hasPermission('reports.view') && Route::has('reports.index')) {
            $actions[] = [
                'label' => __('Open Sales report'),
                'href' => route('reports.index'),
            ];
        }

        if ($user->hasAnyPermission(['reports.view', 'finance.view']) && Route::has('reports.finance')) {
            $actions[] = [
                'label' => __('Open Finance'),
                'href' => route('reports.finance'),
            ];
        }

        if ($user->hasPermission('reports.view') && Route::has('analytics.reports.index')) {
            $actions[] = [
                'label' => __('Export reports'),
                'href' => route('analytics.reports.index'),
            ];
        } elseif ($user->hasPermission('reports.view') && Route::has('reports.index')) {
            $actions[] = [
                'label' => __('Export reports'),
                'href' => route('reports.index').'#export',
            ];
        }

        if ($user->hasPermission('projects.view') && Route::has('projects.executive')) {
            $actions[] = [
                'label' => __('Open Executive Projects'),
                'href' => route('projects.executive'),
            ];
        }

        if ($user->hasPermission('reports.view') && Route::has('analytics.kpis.index')) {
            $actions[] = [
                'label' => __('Open KPI Library'),
                'href' => route('analytics.kpis.index'),
            ];
        }

        if ($user->hasPermission('reports.view') && Route::has('analytics.ai-insights')) {
            $actions[] = [
                'label' => __('Open AI Insights'),
                'href' => route('analytics.ai-insights'),
                'variant' => 'primary',
            ];
        }

        if ($user->hasPermission('reports.view') && Route::has('analytics.executive')) {
            array_unshift($actions, [
                'label' => __('Executive dashboard'),
                'href' => route('analytics.executive'),
                'variant' => 'primary',
            ]);
        }

        return $actions;
    }

    /**
     * @return Collection<int, array{title: string, subtitle: string|null, href: string|null, when: string|null}>
     */
    protected function recentActivity(User $user): Collection
    {
        $items = collect();

        if ($user->hasPermission('audit.view') && Schema::hasTable('audit_logs')) {
            AuditLog::query()
                ->with('user')
                ->latest()
                ->limit(6)
                ->get()
                ->each(function (AuditLog $log) use ($items) {
                    $items->push([
                        'title' => $log->subject ?: $log->event_label,
                        'subtitle' => trim(($log->user?->name ?? __('System')).' · '.$log->event_label),
                        'href' => Route::has('audit-logs.index') ? route('audit-logs.index') : null,
                        'when' => $log->created_at?->diffForHumans(),
                        'at' => $log->created_at,
                    ]);
                });
        }

        if ($user->hasPermission('opportunities.view')) {
            Opportunity::query()
                ->latest('updated_at')
                ->limit(4)
                ->get()
                ->each(function (Opportunity $opportunity) use ($items) {
                    $items->push([
                        'title' => $opportunity->title,
                        'subtitle' => __('Opportunity · :stage', ['stage' => $opportunity->stage_label ?? $opportunity->stage]),
                        'href' => Route::has('pipeline.show') ? route('pipeline.show', $opportunity) : null,
                        'when' => $opportunity->updated_at?->diffForHumans(),
                        'at' => $opportunity->updated_at,
                    ]);
                });
        }

        if ($user->hasPermission('projects.view')) {
            Project::query()
                ->where('is_archived', false)
                ->latest('updated_at')
                ->limit(4)
                ->get()
                ->each(function (Project $project) use ($items) {
                    $items->push([
                        'title' => $project->name,
                        'subtitle' => __('Project updated'),
                        'href' => Route::has('projects.show') ? route('projects.show', $project) : null,
                        'when' => $project->updated_at?->diffForHumans(),
                        'at' => $project->updated_at,
                    ]);
                });
        }

        return $items
            ->sortByDesc(fn ($item) => $item['at']?->timestamp ?? 0)
            ->take(10)
            ->values()
            ->map(fn ($item) => collect($item)->except('at')->all());
    }

    /**
     * @return array{label: string, hint: string}
     */
    protected function projectHealthRollup(User $user, Organization $organization): array
    {
        $exec = $this->executive->forOrganization($organization, $user);
        $atRisk = (int) ($exec['kpis']['at_risk_count'] ?? 0);
        $onTrack = (int) ($exec['kpis']['on_track_count'] ?? 0);
        $active = (int) ($exec['kpis']['active_projects'] ?? 0);

        if ($active === 0) {
            return ['label' => __('—'), 'hint' => __('No active projects')];
        }

        if ($atRisk === 0) {
            return ['label' => __('Healthy'), 'hint' => __(':count on track', ['count' => $onTrack])];
        }

        return [
            'label' => __(':count at risk', ['count' => $atRisk]),
            'hint' => __(':active active · :pct% avg completion', [
                'active' => $active,
                'pct' => $exec['kpis']['average_completion_percentage'] ?? 0,
            ]),
        ];
    }

    protected function auditAnomaliesCount(): int
    {
        return (int) AuditLog::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->whereIn('event', ['deleted', 'status_changed'])
            ->count();
    }

    protected function pipelineTarget(?Organization $organization): float
    {
        if (! $organization) {
            return 0.0;
        }

        $settings = is_array($organization->settings) ? $organization->settings : [];
        $analytics = is_array($settings['analytics'] ?? null) ? $settings['analytics'] : [];
        $sales = is_array($settings['sales'] ?? null) ? $settings['sales'] : [];

        return (float) (
            $analytics['pipeline_target']
            ?? $analytics['sales_target']
            ?? $sales['pipeline_target']
            ?? $sales['target']
            ?? 0
        );
    }

    /**
     * @return list<string>
     */
    protected function openStages(): array
    {
        return config('pipeline.open_stages', ['qualification', 'proposal', 'negotiation']);
    }

    protected function invoiceBalanceColumnsExist(): bool
    {
        return Schema::hasColumn('invoices', 'total') && Schema::hasColumn('invoices', 'amount_paid');
    }

    protected function routeIfExists(string $name, mixed $parameters = []): ?string
    {
        return Route::has($name) ? route($name, $parameters) : null;
    }
}
