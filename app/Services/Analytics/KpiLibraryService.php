<?php

namespace App\Services\Analytics;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeExitProcess;
use App\Models\Invoice;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\Lead;
use App\Models\LeaveApplication;
use App\Models\MarketingAttribution;
use App\Models\MarketingCampaign;
use App\Models\MarketingConversion;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectHealthSnapshot;
use App\Models\ProjectMilestone;
use App\Models\User;
use App\Services\MarketingProviderService;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Schema;

class KpiLibraryService
{
    public function __construct(
        protected TenantContext $tenant,
        protected MarketingProviderService $providers,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function catalog(): array
    {
        $organization = $this->tenant->get();
        $user = auth()->user();

        if (! $organization instanceof Organization || ! $user instanceof User) {
            return config('analytics_kpis.categories', []);
        }

        $categories = config('analytics_kpis.categories', []);

        foreach ($categories as $categoryKey => $category) {
            foreach ($category['kpis'] ?? [] as $kpiKey => $kpi) {
                $resolved = $this->resolveValue($categoryKey, $kpiKey, $organization, $user);
                $categories[$categoryKey]['kpis'][$kpiKey]['resolved_value'] = $resolved;
                $categories[$categoryKey]['kpis'][$kpiKey]['status'] = $this->thresholdStatus(
                    $resolved,
                    $kpi['thresholds'] ?? [],
                    $kpi['unit'] ?? null,
                );
            }
        }

        return $categories;
    }

    public function resolveValue(string $category, string $key, Organization $org, User $user): mixed
    {
        return match ($category) {
            'crm' => $this->resolveCrmKpi($key, $user),
            'projects' => $this->resolveProjectsKpi($key, $org, $user),
            'hrms' => $this->resolveHrmsKpi($key, $user),
            'marketing' => $this->resolveMarketingKpi($key, $org, $user),
            'finance' => $this->resolveFinanceKpi($key, $user),
            'recruitment' => $this->resolveRecruitmentKpi($key, $user),
            default => null,
        };
    }

    protected function resolveCrmKpi(string $key, User $user): mixed
    {
        return match ($key) {
            'open_leads' => $user->hasPermission('leads.view')
                ? Lead::query()->whereNotIn('status', ['won', 'lost', 'converted'])->count()
                : null,
            'pipeline_value' => $user->hasPermission('opportunities.view')
                ? (float) Opportunity::query()->whereIn('stage', $this->openStages())->sum('amount')
                : null,
            'win_rate' => $user->hasPermission('opportunities.view') ? $this->winRatePercent() : null,
            'lead_sources' => $user->hasPermission('leads.view') ? $this->leadSourceDistribution() : null,
            'revenue_forecast' => $user->hasPermission('opportunities.view') ? $this->weightedPipelineValue() : null,
            default => null,
        };
    }

    protected function resolveProjectsKpi(string $key, Organization $org, User $user): mixed
    {
        if (! $user->hasPermission('projects.view')) {
            return null;
        }

        return match ($key) {
            'active_projects' => Project::query()->where('organization_id', $org->id)->where('is_archived', false)->count(),
            'at_risk_projects' => Schema::hasTable('project_health_snapshots')
                ? ProjectHealthSnapshot::query()
                    ->where('organization_id', $org->id)
                    ->whereIn('health_status', ['at_risk', 'delayed', 'red', 'amber', 'critical'])
                    ->count()
                : 0,
            'avg_completion' => round((float) Project::query()
                ->where('organization_id', $org->id)
                ->where('is_archived', false)
                ->avg('completion_percentage'), 1),
            'budget_variance' => $this->budgetVariancePercent($org),
            'milestone_on_time' => $this->milestoneOnTimePercent($org),
            default => null,
        };
    }

    protected function resolveHrmsKpi(string $key, User $user): mixed
    {
        if (! $user->hasAnyPermission(['hrms.view', 'hr.dashboard'])) {
            return null;
        }

        return match ($key) {
            'headcount' => Schema::hasTable('employees')
                ? Employee::query()->whereIn('status', config('hrms.leave_applicable_employee_statuses', []))->count()
                : null,
            'pending_leave' => Schema::hasTable('leave_applications')
                ? LeaveApplication::query()->where('status', 'pending')->count()
                : null,
            'open_openings' => Schema::hasTable('job_openings')
                ? JobOpening::query()->whereIn('status', ['published', 'paused'])->count()
                : null,
            'attrition' => $this->attritionPercent(),
            'attendance_exceptions' => Schema::hasTable('attendance_corrections')
                ? \App\Models\AttendanceCorrection::query()->where('status', 'pending')->count()
                : null,
            default => null,
        };
    }

    protected function resolveMarketingKpi(string $key, Organization $org, User $user): mixed
    {
        if (! $user->hasAnyPermission(['marketing.view', 'integrations.view', 'integrations.manage'])) {
            return null;
        }

        return match ($key) {
            'attributed_leads' => Schema::hasTable('marketing_attributions')
                ? MarketingAttribution::query()->where('organization_id', $org->id)->whereNotNull('lead_id')->count()
                : null,
            'cost_per_lead' => $this->costPerLead($org),
            'conversion_rate' => $this->marketingConversionRate($org),
            'campaign_roi' => $this->campaignRoiPercent($org),
            'provider_health' => $this->providerHealthPercent($org),
            default => null,
        };
    }

    protected function resolveFinanceKpi(string $key, User $user): mixed
    {
        return match ($key) {
            'outstanding_ar' => $user->hasPermission('invoices.view') && $this->invoiceBalanceColumnsExist()
                ? (float) Invoice::query()
                    ->whereNotIn('status', ['cancelled', 'draft'])
                    ->selectRaw('COALESCE(SUM(total - amount_paid), 0) as outstanding')
                    ->value('outstanding')
                : null,
            'payments_period' => $user->hasPermission('payments.view') && Schema::hasTable('payments')
                ? (float) Payment::query()->where('payment_date', '>=', now()->startOfMonth())->sum('amount')
                : null,
            'overdue_invoices' => $user->hasPermission('invoices.view') && Schema::hasColumn('invoices', 'due_date')
                ? Invoice::query()
                    ->whereNotIn('status', ['cancelled', 'draft', 'paid'])
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->count()
                : null,
            default => null,
        };
    }

    protected function resolveRecruitmentKpi(string $key, User $user): mixed
    {
        if (! $user->hasPermission('recruitment.view')) {
            return null;
        }

        return match ($key) {
            'pipeline_candidates' => Schema::hasTable('job_applications')
                ? JobApplication::query()->whereNotIn('stage', ['hired', 'rejected', 'withdrawn'])->count()
                : null,
            'interviews_scheduled' => Schema::hasTable('interview_rounds')
                ? \App\Models\InterviewRound::query()
                    ->where('status', 'scheduled')
                    ->where('scheduled_at', '>=', now())
                    ->count()
                : null,
            'offers_pending' => Schema::hasTable('offer_letters')
                ? \App\Models\OfferLetter::query()->whereIn('status', ['draft', 'pending_approval', 'approved', 'sent'])->count()
                : null,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $thresholds
     */
    protected function thresholdStatus(mixed $value, array $thresholds, ?string $unit): ?string
    {
        if ($value === null || ! is_numeric($value) || $thresholds === []) {
            return null;
        }

        $numeric = (float) $value;
        $critical = isset($thresholds['critical']) ? (float) $thresholds['critical'] : null;
        $warning = isset($thresholds['warning']) ? (float) $thresholds['warning'] : null;

        if ($critical === null && $warning === null) {
            return null;
        }

        $higherIsBad = in_array($unit, ['count', 'percent', 'currency'], true)
            && ($critical !== null && $warning !== null && $critical >= $warning);

        if ($higherIsBad) {
            if ($critical !== null && $numeric >= $critical) {
                return 'critical';
            }
            if ($warning !== null && $numeric >= $warning) {
                return 'warning';
            }

            return 'ok';
        }

        if ($critical !== null && $numeric <= $critical) {
            return 'critical';
        }
        if ($warning !== null && $numeric <= $warning) {
            return 'warning';
        }

        return 'ok';
    }

    /**
     * @return list<string>
     */
    protected function openStages(): array
    {
        return config('pipeline.open_stages', ['qualification', 'proposal', 'negotiation']);
    }

    protected function winRatePercent(): ?float
    {
        $won = Opportunity::query()->where('stage', 'closed_won')->count();
        $lost = Opportunity::query()->where('stage', 'closed_lost')->count();
        $closed = $won + $lost;

        return $closed > 0 ? round(($won / $closed) * 100, 1) : null;
    }

    /**
     * @return array<string, int>
     */
    protected function leadSourceDistribution(): array
    {
        if (! Schema::hasColumn('leads', 'source')) {
            return [];
        }

        return Lead::query()
            ->selectRaw('COALESCE(source, ?) as source_label, COUNT(*) as total', [__('Unknown')])
            ->groupBy('source_label')
            ->pluck('total', 'source_label')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    protected function weightedPipelineValue(): float
    {
        $openStages = $this->openStages();

        return (float) Opportunity::query()
            ->whereIn('stage', $openStages)
            ->get(['amount', 'probability'])
            ->sum(function (Opportunity $opportunity) {
                $amount = (float) $opportunity->amount;
                $probability = max(0, min(100, (int) ($opportunity->probability ?? 0)));

                return $amount * ($probability / 100);
            });
    }

    protected function budgetVariancePercent(Organization $org): ?float
    {
        if (! Schema::hasTable('project_budgets')) {
            $planned = (float) Project::query()->where('organization_id', $org->id)->where('is_archived', false)->sum('estimated_budget');
            $actual = (float) Project::query()->where('organization_id', $org->id)->where('is_archived', false)->sum('actual_budget');
        } else {
            $planned = (float) \App\Models\ProjectBudget::query()->where('organization_id', $org->id)->sum('planned_total');
            $actual = (float) \App\Models\ProjectBudget::query()->where('organization_id', $org->id)->sum('actual_total');
        }

        if ($planned <= 0) {
            return null;
        }

        return round((abs($actual - $planned) / $planned) * 100, 1);
    }

    protected function milestoneOnTimePercent(Organization $org): ?float
    {
        if (! Schema::hasTable('project_milestones')) {
            return null;
        }

        $milestones = ProjectMilestone::query()
            ->whereHas('project', fn ($q) => $q->where('organization_id', $org->id)->where('is_archived', false))
            ->whereNotNull('due_date')
            ->get(['status', 'due_date']);

        if ($milestones->isEmpty()) {
            return null;
        }

        $onTime = $milestones->filter(function (ProjectMilestone $milestone) {
            if (in_array($milestone->status, ['completed', 'cancelled'], true)) {
                return $milestone->status === 'completed';
            }

            return $milestone->due_date && $milestone->due_date->isFuture();
        })->count();

        return round(($onTime / $milestones->count()) * 100, 1);
    }

    protected function attritionPercent(): ?float
    {
        if (! Schema::hasTable('employees')) {
            return null;
        }

        $activeStatuses = config('hrms.leave_applicable_employee_statuses', []);
        $headcount = Employee::query()->whereIn('status', $activeStatuses)->count();
        if ($headcount <= 0) {
            return null;
        }

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

        return round(($exits / $headcount) * 100, 1);
    }

    protected function costPerLead(Organization $org): ?float
    {
        if (! Schema::hasTable('marketing_campaigns') || ! Schema::hasTable('marketing_attributions')) {
            return null;
        }

        $leads = MarketingAttribution::query()->where('organization_id', $org->id)->whereNotNull('lead_id')->count();
        $budget = (float) MarketingCampaign::query()
            ->where('organization_id', $org->id)
            ->where('status', MarketingCampaign::STATUS_ACTIVE)
            ->sum('budget_amount');

        return $leads > 0 && $budget > 0 ? round($budget / $leads, 2) : null;
    }

    protected function marketingConversionRate(Organization $org): ?float
    {
        if (! Schema::hasTable('marketing_attributions') || ! Schema::hasTable('marketing_conversions')) {
            return null;
        }

        $leads = MarketingAttribution::query()->where('organization_id', $org->id)->whereNotNull('lead_id')->count();
        $conversions = MarketingConversion::query()->where('organization_id', $org->id)->count();

        return $leads > 0 ? round(($conversions / $leads) * 100, 1) : null;
    }

    protected function campaignRoiPercent(Organization $org): ?float
    {
        if (! Schema::hasTable('marketing_campaigns') || ! Schema::hasTable('marketing_conversions')) {
            return null;
        }

        $budget = (float) MarketingCampaign::query()->where('organization_id', $org->id)->sum('budget_amount');
        $value = (float) MarketingConversion::query()->where('organization_id', $org->id)->sum('event_value');

        return $budget > 0 ? round((($value - $budget) / $budget) * 100, 1) : null;
    }

    protected function providerHealthPercent(Organization $org): ?float
    {
        $cards = collect($this->providers->integrationCardsForOrganization($org));
        if ($cards->isEmpty()) {
            return null;
        }

        $healthy = $cards->filter(function (array $card) {
            if (! empty($card['last_error']) || ($card['status'] ?? null) === 'error') {
                return false;
            }

            return ($card['connected'] ?? false) || ($card['status'] ?? null) === 'connected';
        })->count();

        return round(($healthy / $cards->count()) * 100, 1);
    }

    protected function invoiceBalanceColumnsExist(): bool
    {
        return Schema::hasColumn('invoices', 'total') && Schema::hasColumn('invoices', 'amount_paid');
    }
}
