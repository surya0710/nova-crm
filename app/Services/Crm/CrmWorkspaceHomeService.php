<?php

namespace App\Services\Crm;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use App\Models\UserUiPreference;
use App\Services\LeadFollowUpService;
use App\Services\LeadVisibilityService;
use App\Services\Navigation\ShellQuickActionService;
use App\Services\TenantContext;
use App\Services\Workspace\CachesWorkspaceHome;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class CrmWorkspaceHomeService
{
    use CachesWorkspaceHome;

    public function __construct(
        protected LeadFollowUpService $followUps,
        protected LeadVisibilityService $leadVisibility,
        protected TenantContext $tenant,
        protected ShellQuickActionService $shellQuickActions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        return $this->rememberHome('crm', $user, fn () => $this->buildUncached($user));
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

        return [
            'kpis' => $this->kpis($user),
            'attention' => $this->attention($user),
            'followUps' => $this->todaysFollowUps($user),
            'assignedLeads' => $this->assignedLeads($user),
            'pipelineSummary' => $this->pipelineSummary($user),
            'revenueSummary' => $this->revenueSummary($user),
            'recentCustomers' => $this->recentCustomers($user),
            'recentOpportunities' => $this->recentOpportunities($user),
            'tasksDueToday' => $this->tasksDueToday($user),
            'recentActivity' => $this->recentActivity($user),
            'quickActions' => $this->quickActions($user, $organization),
            'pinnedPages' => $this->pinnedCrmPages($prefs),
            'favoriteReports' => $this->favoriteReports($user, $prefs),
            'widgetLayout' => $prefs?->dashboard_layout['crm'] ?? null,
        ];
    }

    /**
     * @return array<int, array{label: string, value: string|int, hint?: string|null}>
     */
    protected function kpis(User $user): array
    {
        $kpis = [];

        if ($user->hasPermission('leads.view')) {
            $organization = $this->tenant->get();
            $openLeads = $this->leadVisibility->visibleQuery($user, $organization)
                ->whereNotIn('status', ['won', 'lost', 'converted'])
                ->count();
            $kpis[] = [
                'label' => __('Open leads'),
                'value' => $openLeads,
                'hint' => __('Active pipeline intake'),
            ];
        }

        if ($user->hasPermission('opportunities.view')) {
            $openStages = config('pipeline.open_stages', ['qualification', 'proposal', 'negotiation']);
            $pipelineValue = (float) Opportunity::query()
                ->whereIn('stage', $openStages)
                ->sum('amount');
            $kpis[] = [
                'label' => __('Pipeline value'),
                'value' => number_format($pipelineValue, 0),
                'hint' => __('Open opportunities'),
            ];
            $openDeals = Opportunity::query()
                ->whereIn('stage', $openStages)
                ->count();
            $kpis[] = [
                'label' => __('Open deals'),
                'value' => $openDeals,
            ];
        }

        if ($user->hasPermission('customers.view')) {
            $kpis[] = [
                'label' => __('Customers'),
                'value' => Customer::query()->count(),
            ];
        }

        if ($user->hasPermission('leads.view')) {
            $organization = $this->tenant->get();
            $dueToday = $this->leadVisibility->visibleQuery($user, $organization)
                ->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '<=', $this->followUps->organizationNow()->endOfDay()->utc())
                ->where('next_follow_up_at', '>=', $this->followUps->organizationNow()->startOfDay()->utc())
                ->count();
            $kpis[] = [
                'label' => __('Follow-ups today'),
                'value' => $dueToday,
                'hint' => __('Scheduled for today'),
            ];
        }

        if ($user->hasPermission('invoices.view') && Schema::hasColumn('invoices', 'total') && Schema::hasColumn('invoices', 'amount_paid')) {
            $outstanding = (float) Invoice::query()
                ->whereNotIn('status', ['cancelled', 'draft'])
                ->selectRaw('COALESCE(SUM(total - amount_paid), 0) as outstanding')
                ->value('outstanding');
            $kpis[] = [
                'label' => __('Outstanding AR'),
                'value' => number_format($outstanding, 0),
                'hint' => __('Invoices balance due'),
            ];
        }

        return array_slice($kpis, 0, 6);
    }

    /**
     * @return array{outstanding: float|null, invoices: int, payments: int}|null
     */
    protected function revenueSummary(User $user): ?array
    {
        if (! $user->hasAnyPermission(['invoices.view', 'payments.view', 'quotations.view'])) {
            return null;
        }

        $outstanding = null;
        if ($user->hasPermission('invoices.view') && Schema::hasColumn('invoices', 'total') && Schema::hasColumn('invoices', 'amount_paid')) {
            $outstanding = (float) Invoice::query()
                ->whereNotIn('status', ['cancelled', 'draft'])
                ->selectRaw('COALESCE(SUM(total - amount_paid), 0) as outstanding')
                ->value('outstanding');
        }

        return [
            'outstanding' => $outstanding,
            'invoices' => $user->hasPermission('invoices.view') ? Invoice::query()->count() : 0,
            'payments' => $user->hasPermission('payments.view') ? \App\Models\Payment::query()->count() : 0,
            'href' => Route::has('crm.revenue') ? route('crm.revenue') : null,
        ];
    }

    /**
     * @return Collection<int, array{title: string, subtitle?: string|null, href?: string|null, badge?: string|null}>
     */
    protected function attention(User $user): Collection
    {
        if (! $user->hasPermission('leads.view')) {
            return collect();
        }

        return $this->followUps->dueForAlertPayloads($user, 8)->map(fn (array $item) => [
            'title' => $item['name'],
            'subtitle' => $item['company'] ?: ($item['next_follow_up_at_formatted'] ?? null),
            'href' => $item['url'],
            'badge' => __('Due'),
        ]);
    }

    /**
     * @return Collection<int, Lead>
     */
    protected function todaysFollowUps(User $user): Collection
    {
        if (! $user->hasPermission('leads.view')) {
            return collect();
        }

        $start = $this->followUps->organizationNow()->copy()->startOfDay()->utc();
        $end = $this->followUps->organizationNow()->copy()->endOfDay()->utc();

        return $this->leadVisibility->visibleQuery($user, $this->tenant->get())
            ->with('assignee')
            ->whereNotNull('next_follow_up_at')
            ->whereBetween('next_follow_up_at', [$start, $end])
            ->orderBy('next_follow_up_at')
            ->limit(8)
            ->get();
    }

    /**
     * @return Collection<int, Lead>
     */
    protected function assignedLeads(User $user): Collection
    {
        if (! $user->hasPermission('leads.view')) {
            return collect();
        }

        // Product "My assigned" list — always the current user; visibility is inherent.
        return $this->leadVisibility->visibleQuery($user, $this->tenant->get())
            ->with('assignee')
            ->where('assigned_to', $user->id)
            ->whereNotIn('status', ['won', 'lost', 'converted'])
            ->latest()
            ->limit(8)
            ->get();
    }

    /**
     * @return Collection<int, array{stage: string, count: int, value: float}>
     */
    protected function pipelineSummary(User $user): Collection
    {
        if (! $user->hasPermission('opportunities.view')) {
            return collect();
        }

        $openStages = config('pipeline.open_stages', ['qualification', 'proposal', 'negotiation']);

        return Opportunity::query()
            ->selectRaw('stage, COUNT(*) as aggregate_count, COALESCE(SUM(amount), 0) as aggregate_value')
            ->whereIn('stage', $openStages)
            ->groupBy('stage')
            ->orderBy('stage')
            ->get()
            ->map(fn ($row) => [
                'stage' => (string) $row->stage,
                'count' => (int) $row->aggregate_count,
                'value' => (float) $row->aggregate_value,
            ]);
    }

    /**
     * @return Collection<int, Customer>
     */
    protected function recentCustomers(User $user): Collection
    {
        if (! $user->hasPermission('customers.view')) {
            return collect();
        }

        return Customer::query()->latest()->limit(6)->get();
    }

    /**
     * @return Collection<int, Opportunity>
     */
    protected function recentOpportunities(User $user): Collection
    {
        if (! $user->hasPermission('opportunities.view')) {
            return collect();
        }

        return Opportunity::query()->with('customer')->latest()->limit(6)->get();
    }

    /**
     * @return Collection<int, Task>
     */
    protected function tasksDueToday(User $user): Collection
    {
        if (! $user->hasPermission('tasks.view')) {
            return collect();
        }

        $today = $this->followUps->organizationNow()->toDateString();

        return Task::query()
            ->with('assignee')
            ->whereDate('due_date', $today)
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereNotIn('status', ['completed', 'cancelled', 'done']);
            })
            ->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhere('created_by', $user->id);
            })
            ->orderBy('due_date')
            ->limit(8)
            ->get();
    }

    /**
     * @return Collection<int, array{title: string, subtitle: string|null, href: string|null, when: string|null}>
     */
    protected function recentActivity(User $user): Collection
    {
        $items = collect();

        if ($user->hasPermission('leads.view')) {
            $this->leadVisibility->visibleQuery($user, $this->tenant->get())
                ->latest('updated_at')
                ->limit(5)
                ->get()
                ->each(function (Lead $lead) use ($items) {
                    $items->push([
                        'title' => $lead->name,
                        'subtitle' => __('Lead updated · :status', ['status' => $lead->status_label]),
                        'href' => route('leads.show', $lead),
                        'when' => $lead->updated_at?->diffForHumans(),
                        'at' => $lead->updated_at,
                    ]);
                });
        }

        if ($user->hasPermission('customers.view')) {
            Customer::query()
                ->latest('updated_at')
                ->limit(4)
                ->get()
                ->each(function (Customer $customer) use ($items) {
                    $items->push([
                        'title' => $customer->display_name ?? $customer->name,
                        'subtitle' => __('Customer updated'),
                        'href' => route('customers.show', $customer),
                        'when' => $customer->updated_at?->diffForHumans(),
                        'at' => $customer->updated_at,
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
                        'subtitle' => __('Opportunity · :stage', ['stage' => $opportunity->stage]),
                        'href' => route('pipeline.show', $opportunity),
                        'when' => $opportunity->updated_at?->diffForHumans(),
                        'at' => $opportunity->updated_at,
                    ]);
                });
        }

        return $items
            ->sortByDesc(fn ($item) => $item['at']?->timestamp ?? 0)
            ->take(10)
            ->values();
    }

    /**
     * @return array{primary: array<int, array{label: string, href: string, variant?: string}>, overflow: array<int, array{label: string, href: string, variant?: string}>, all: array<int, array{label: string, href: string, variant?: string}>}
     */
    protected function quickActions(User $user, $organization): array
    {
        if (! $organization) {
            return ['primary' => [], 'overflow' => [], 'all' => []];
        }

        return $this->shellQuickActions->forWorkspace($user, $organization, 'crm');
    }

    /**
     * @return Collection<int, array{label: string, href: string}>
     */
    protected function pinnedCrmPages(?UserUiPreference $prefs): Collection
    {
        $pinned = collect($prefs?->pinned_pages ?? []);

        return $pinned
            ->filter(function ($page) {
                $href = is_array($page) ? ($page['href'] ?? '') : '';
                $workspace = is_array($page) ? ($page['workspace'] ?? null) : null;

                return $href && ($workspace === 'crm' || str_contains($href, '/leads') || str_contains($href, '/customers') || str_contains($href, '/pipeline') || str_contains($href, '/crm'));
            })
            ->map(fn ($page) => [
                'label' => $page['label'] ?? ($page['title'] ?? __('Pinned page')),
                'href' => $page['href'],
            ])
            ->values();
    }

    /**
     * @return Collection<int, array{label: string, href: string}>
     */
    protected function favoriteReports(User $user, ?UserUiPreference $prefs): Collection
    {
        if (! $user->hasPermission('reports.view')) {
            return collect();
        }

        $favorites = collect($prefs?->favorites ?? [])
            ->filter(fn ($item) => is_array($item) && str_contains($item['href'] ?? '', '/reports'))
            ->map(fn ($item) => [
                'label' => $item['label'] ?? __('Report'),
                'href' => $item['href'],
            ]);

        if ($favorites->isEmpty() && Route::has('reports.finance')) {
            $favorites = collect([
                ['label' => __('Finance report'), 'href' => route('reports.finance')],
            ]);
        }

        return $favorites->values();
    }
}
