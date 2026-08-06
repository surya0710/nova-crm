@php
    $currency = $data['currency'];
    $formatMoney = fn (float $amount) => number_format($amount, 2).' '.$currency;
    $maxMonthly = max(1, $data['monthly_revenue']->max('total') ?: 1);
    $maxLeadCount = max(1, $data['lead_counts']->max() ?: 1);
@endphp

<x-app-layout>
    <x-layouts.analytics
        :title="__('Reports & Analytics')"
        :subtitle="$organization->name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => __('Reports'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <x-forms.select name="period" onchange="this.form.submit()" class="!w-auto min-w-[10rem]">
                    <option value="30" @selected($period === '30')>{{ __('Last 30 days') }}</option>
                    <option value="90" @selected($period === '90')>{{ __('Last 90 days') }}</option>
                    <option value="365" @selected($period === '365')>{{ __('Last 12 months') }}</option>
                    <option value="all" @selected($period === 'all')>{{ __('All time') }}</option>
                </x-forms.select>
                <x-forms.select name="group_by" onchange="this.form.submit()" class="!w-auto min-w-[10rem]" aria-label="{{ __('Group geographic reports by') }}">
                    <option value="state" @selected($groupBy === 'state')>{{ __('Group by state') }}</option>
                    <option value="country" @selected($groupBy === 'country')>{{ __('Group by country') }}</option>
                </x-forms.select>
                @if (auth()->user()->hasPermission('reports.view') || auth()->user()->hasPermission('finance.view'))
                    <x-ui.button :href="route('reports.finance')" variant="secondary" size="sm">{{ __('Finance reports') }}</x-ui.button>
                @endif
            </form>
        </x-slot:actions>

        <x-slot:kpis>
            <x-ui.stat-card
                :label="__('Revenue collected')"
                :value="$formatMoney($data['revenue_collected'])"
                :hint="__('From recorded :label', ['label' => strtolower(crm_term('payments'))])"
            />
            <x-ui.stat-card
                :label="__('Outstanding')"
                :value="$formatMoney($data['outstanding_amount'])"
                :hint="__(':count unpaid :label', ['count' => $data['outstanding_count'], 'label' => strtolower(crm_term('invoices'))])"
            />
            <x-ui.stat-card
                :label="__('Pipeline value')"
                :value="$formatMoney($data['open_pipeline_value'])"
                :hint="__('Open :label', ['label' => strtolower(crm_term('deals'))])"
            />
            <x-ui.stat-card
                :label="__('Win rate')"
                :value="$data['conversion_rate'] !== null ? $data['conversion_rate'].'%' : '—'"
                :hint="__(':label closed won vs lost', ['label' => crm_term('leads')])"
            />
        </x-slot:kpis>

        <div class="lg:col-span-2 space-y-6">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <x-entity.section
                    class="xl:col-span-2"
                    :title="__('Monthly revenue')"
                    :subtitle="__('Collected :label over the last 6 months', ['label' => strtolower(crm_term('payments'))])"
                >
                    @if ($data['monthly_revenue']->every(fn ($m) => $m['total'] == 0))
                        <p class="text-sm text-ink-muted text-center py-8">{{ __('No revenue recorded yet.') }}</p>
                    @else
                        <div class="flex items-end justify-between gap-3 h-48">
                            @foreach ($data['monthly_revenue'] as $month)
                                <div class="flex-1 flex flex-col items-center gap-2 min-w-0">
                                    <span class="text-xs font-medium text-ink truncate w-full text-center">{{ number_format($month['total'], 0) }}</span>
                                    <div class="w-full flex items-end justify-center h-32">
                                        <div
                                            class="w-full max-w-12 rounded-t-lg bg-primary-500 transition-all"
                                            style="height: {{ max(4, ($month['total'] / $maxMonthly) * 100) }}%"
                                            title="{{ $formatMoney($month['total']) }}"
                                        ></div>
                                    </div>
                                    <span class="text-[10px] text-ink-muted truncate w-full text-center">{{ $month['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-entity.section>

                <x-entity.section :title="__('Payment methods')" :subtitle="__('Breakdown for selected period')">
                    @if ($data['payments_by_method']->isEmpty())
                        <p class="text-sm text-ink-muted text-center py-8">{{ __('No payments in this period.') }}</p>
                    @else
                        <div class="space-y-4">
                            @foreach ($data['payments_by_method'] as $row)
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="font-medium text-ink">{{ config('payments.methods.'.$row->method, ucfirst(str_replace('_', ' ', $row->method))) }}</span>
                                        <span class="text-ink-muted">{{ $formatMoney((float) $row->total) }}</span>
                                    </div>
                                    <p class="text-xs text-ink-muted">{{ trans_choice(':count payment|:count payments', $row->count, ['count' => $row->count]) }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-entity.section>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <x-entity.section
                    :title="__(':label funnel', ['label' => crm_term('leads')])"
                    :subtitle="__(':count total', ['count' => $data['lead_total']])"
                >
                    <div class="space-y-3">
                        @forelse (config('leads.statuses') as $status => $label)
                            @php $count = (int) ($data['lead_counts'][$status] ?? 0); @endphp
                            @if ($count > 0 || in_array($status, ['new', 'won', 'lost']))
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-ink">{{ $label }}</span>
                                        <span class="font-medium text-ink-heading">{{ $count }}</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-surface-muted overflow-hidden">
                                        <div class="h-full rounded-full bg-primary-500" style="width: {{ ($count / $maxLeadCount) * 100 }}%"></div>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <p class="text-sm text-ink-muted">{{ __('No leads yet.') }}</p>
                        @endforelse
                    </div>
                </x-entity.section>

                <x-entity.section :title="crm_term('pipeline')" :subtitle="__('Opportunities by stage')">
                    @php $hasOpportunities = $data['opportunity_by_stage']->isNotEmpty(); @endphp
                    @if ($hasOpportunities)
                        <div class="space-y-3">
                            @foreach (config('pipeline.stages') as $stage => $label)
                                @php
                                    $row = $data['opportunity_by_stage'][$stage] ?? null;
                                    $count = $row ? (int) $row->count : 0;
                                    $value = $row ? (float) $row->value : 0;
                                @endphp
                                <div class="flex items-center justify-between gap-4 py-2 border-b border-line last:border-0">
                                    <div>
                                        <p class="text-sm font-medium text-ink">{{ $label }}</p>
                                        <p class="text-xs text-ink-muted">{{ trans_choice(':count deal|:count deals', $count, ['count' => $count]) }}</p>
                                    </div>
                                    <p class="text-sm font-semibold text-ink-heading shrink-0">{{ $formatMoney($value) }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-ink-muted">{{ __('No pipeline data yet.') }}</p>
                    @endif
                </x-entity.section>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <x-entity.section :title="__('Leads by :group', ['group' => $groupBy])">
                    <div class="space-y-2">
                        @forelse ($data['leads_by_geography'] as $row)
                            <div class="flex justify-between text-sm">
                                <span class="text-ink-muted">{{ $row->{$groupBy} }}</span>
                                <span class="font-medium text-ink-heading">{{ number_format((int) $row->count) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-ink-muted">{{ __('No geographic lead data yet.') }}</p>
                        @endforelse
                    </div>
                </x-entity.section>

                <x-entity.section :title="__('Customers by :group', ['group' => $groupBy])">
                    <div class="space-y-2">
                        @forelse ($data['customers_by_geography'] as $row)
                            <div class="flex justify-between text-sm">
                                <span class="text-ink-muted">{{ $row->{$groupBy} }}</span>
                                <span class="font-medium text-ink-heading">{{ number_format((int) $row->count) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-ink-muted">{{ __('No geographic customer data yet.') }}</p>
                        @endforelse
                    </div>
                </x-entity.section>

                <x-entity.section :title="__('Revenue by :group', ['group' => $groupBy])">
                    <div class="space-y-2">
                        @forelse ($data['revenue_by_geography'] as $row)
                            <div class="flex justify-between text-sm">
                                <span class="text-ink-muted">{{ $row->geography }}</span>
                                <span class="font-medium text-ink-heading">{{ $formatMoney((float) $row->total) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-ink-muted">{{ __('No geographic revenue data yet.') }}</p>
                        @endforelse
                    </div>
                </x-entity.section>

                <x-entity.section :title="__('Lead conversion by :group', ['group' => $groupBy])">
                    <div class="space-y-2">
                        @forelse ($data['lead_conversion_by_geography'] as $row)
                            <div class="flex justify-between gap-4 text-sm">
                                <span class="text-ink-muted">{{ $row['geography'] }}</span>
                                <span class="font-medium text-ink-heading">{{ $row['converted'] }}/{{ $row['total'] }} · {{ $row['rate'] }}%</span>
                            </div>
                        @empty
                            <p class="text-sm text-ink-muted">{{ __('No geographic conversion data yet.') }}</p>
                        @endforelse
                    </div>
                </x-entity.section>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <x-entity.section :title="crm_term('invoices')">
                    <div class="space-y-2">
                        @foreach (config('invoices.statuses') as $status => $label)
                            @php $count = (int) ($data['invoice_counts'][$status] ?? 0); @endphp
                            <div class="flex justify-between text-sm">
                                <span class="text-ink-muted">{{ $label }}</span>
                                <span class="font-medium text-ink-heading">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-entity.section>

                <x-entity.section :title="crm_term('quotations')">
                    <div class="space-y-2">
                        @foreach (config('quotations.statuses') as $status => $label)
                            @php $count = (int) ($data['quotation_counts'][$status] ?? 0); @endphp
                            <div class="flex justify-between text-sm">
                                <span class="text-ink-muted">{{ $label }}</span>
                                <span class="font-medium text-ink-heading">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-entity.section>

                <x-entity.section
                    :title="__('Top performers')"
                    :subtitle="__('Won :label by assignee', ['label' => strtolower(crm_term('leads'))])"
                >
                    @if ($data['top_performers']->isEmpty())
                        <p class="text-sm text-ink-muted">{{ __('No closed wins yet.') }}</p>
                    @else
                        <div class="space-y-3">
                            @foreach ($data['top_performers'] as $performer)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-ink">{{ $performer['name'] }}</span>
                                    <span class="text-sm font-semibold text-primary-600">{{ $performer['count'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-entity.section>
            </div>
        </div>
    </x-layouts.analytics>
</x-app-layout>
