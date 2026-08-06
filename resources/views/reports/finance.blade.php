@php
    $currency = $data['currency'];
    $formatMoney = fn (float $amount) => number_format($amount, 2).' '.$currency;
    $maxMonthly = max(1, collect($data['revenue_by_month'])->max('total') ?: 1);
    $maxAging = max(1, collect($data['aging'])->max('total') ?: 1);
@endphp

<x-app-layout>
    <x-layouts.analytics
        :title="__('Accounts Receivable & Revenue')"
        :subtitle="$organization->name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => __('Reports'), 'href' => route('crm.reports')],
                ['label' => __('Finance'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button :href="route('reports.index')" variant="secondary" size="sm">{{ __('General reports') }}</x-ui.button>
                @if (auth()->user()->hasPermission('reports.manage') || auth()->user()->hasPermission('finance.view'))
                    <x-ui.button :href="route('reports.export.revenue', request()->query())" variant="secondary" size="sm">
                        {{ __('Export revenue CSV') }}
                    </x-ui.button>
                    <x-ui.button :href="route('reports.export.outstanding', request()->query())" variant="secondary" size="sm">
                        {{ __('Export outstanding CSV') }}
                    </x-ui.button>
                @endif
            </div>
        </x-slot:actions>

        <div class="lg:col-span-2 space-y-6">
            <x-ui.card>
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
                    <x-forms.field :label="__('Period')" name="period">
                        <x-forms.select id="period" name="period">
                            <option value="30" @selected(($filters['period'] ?? '30') === '30')>{{ __('Last 30 days') }}</option>
                            <option value="90" @selected(($filters['period'] ?? '') === '90')>{{ __('Last 90 days') }}</option>
                            <option value="365" @selected(($filters['period'] ?? '') === '365')>{{ __('Last 12 months') }}</option>
                            <option value="all" @selected(($filters['period'] ?? '') === 'all')>{{ __('All time') }}</option>
                            <option value="custom" @selected(($filters['period'] ?? '') === 'custom')>{{ __('Custom range') }}</option>
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('From')" name="date_from">
                        <x-forms.input id="date_from" name="date_from" type="date" :value="$filters['date_from'] ?? ''" />
                    </x-forms.field>
                    <x-forms.field :label="__('To')" name="date_to">
                        <x-forms.input id="date_to" name="date_to" type="date" :value="$filters['date_to'] ?? ''" />
                    </x-forms.field>
                    <x-forms.field :label="crm_term('customer')" name="customer_id">
                        <x-forms.select id="customer_id" name="customer_id">
                            <option value="">{{ __('All customers') }}</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) ($filters['customer_id'] ?? '') === (string) $customer->id)>{{ $customer->display_name }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Salesperson')" name="salesperson_id">
                        <x-forms.select id="salesperson_id" name="salesperson_id">
                            <option value="">{{ __('All salespeople') }}</option>
                            @foreach ($salespeople as $person)
                                <option value="{{ $person->id }}" @selected((string) ($filters['salesperson_id'] ?? '') === (string) $person->id)>{{ $person->name }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Invoice status')" name="status">
                        <x-forms.select id="status" name="status">
                            <option value="">{{ __('All statuses') }}</option>
                            @foreach (config('invoices.statuses') as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <div class="sm:col-span-2 lg:col-span-6">
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <x-ui.stat-card
                    :label="__('Outstanding receivables')"
                    :value="$formatMoney($data['outstanding_receivables'])"
                    :hint="trans_choice(':count invoice|:count invoices', $data['outstanding_count'], ['count' => $data['outstanding_count']])"
                />
                <x-ui.stat-card
                    :label="__('Total paid')"
                    :value="$formatMoney($data['total_paid'])"
                    :hint="__('Collected in selected period')"
                />
                <x-ui.stat-card
                    :label="__('Total invoiced')"
                    :value="$formatMoney($data['total_invoiced'])"
                    :hint="trans_choice(':count invoice|:count invoices', $data['invoice_count'], ['count' => $data['invoice_count']])"
                />
                <x-ui.stat-card
                    :label="__('Collected this month')"
                    :value="$formatMoney($data['collected_this_month'])"
                    :hint="now()->format('F Y')"
                />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                @foreach ([
                    ['label' => __('Paid invoices'), 'value' => number_format($data['paid_invoice_count'])],
                    ['label' => __('Average invoice value'), 'value' => $formatMoney($data['average_invoice_value'])],
                    ['label' => __('Average payment value'), 'value' => $formatMoney($data['average_payment_value'])],
                    ['label' => __('Collection rate'), 'value' => $data['collection']['collection_rate'] !== null ? $data['collection']['collection_rate'].'%' : '—'],
                ] as $stat)
                    <x-ui.stat-card :label="$stat['label']" :value="$stat['value']" />
                @endforeach
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <x-entity.section class="xl:col-span-2" :title="__('Revenue by month')">
                    @if (collect($data['revenue_by_month'])->every(fn ($m) => $m['total'] == 0))
                        <p class="text-sm text-ink-muted text-center py-8">{{ __('No revenue recorded yet.') }}</p>
                    @else
                        <div class="flex items-end justify-between gap-2 h-48 overflow-x-auto">
                            @foreach ($data['revenue_by_month'] as $month)
                                <div class="flex-1 flex flex-col items-center gap-2 min-w-[3rem]">
                                    <span class="text-xs font-medium text-ink">{{ number_format($month['total'], 0) }}</span>
                                    <div class="w-full flex items-end justify-center h-32">
                                        <div class="w-full max-w-10 rounded-t-lg bg-primary-500" style="height: {{ max(4, ($month['total'] / $maxMonthly) * 100) }}%"></div>
                                    </div>
                                    <span class="text-[10px] text-ink-muted text-center">{{ $month['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-entity.section>

                <x-entity.section :title="__('Collection metrics')">
                    <div class="space-y-3">
                        @foreach ([
                            ['label' => __('Collection rate'), 'value' => $data['collection']['collection_rate'] !== null ? $data['collection']['collection_rate'].'%' : '—'],
                            ['label' => __('Outstanding %'), 'value' => $data['collection']['outstanding_percent'] !== null ? $data['collection']['outstanding_percent'].'%' : '—'],
                            ['label' => __('Paid %'), 'value' => $data['collection']['paid_percent'] !== null ? $data['collection']['paid_percent'].'%' : '—'],
                            ['label' => __('Avg. days to payment'), 'value' => $data['collection']['average_days_to_payment'] !== null ? $data['collection']['average_days_to_payment'] : '—'],
                            ['label' => __('Payment count'), 'value' => number_format($data['collection']['payment_count'])],
                        ] as $row)
                            <div class="flex justify-between text-sm">
                                <span class="text-ink-muted">{{ $row['label'] }}</span>
                                <span class="font-medium text-ink-heading">{{ $row['value'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-entity.section>
            </div>

            <x-entity.section :title="__('Invoice aging')" :subtitle="__('Outstanding balances by days overdue')">
                <div class="grid grid-cols-1 sm:grid-cols-5 gap-4 mb-6">
                    @foreach ($data['aging'] as $bucket)
                        <div class="text-center p-4 rounded-lg bg-surface-muted/50">
                            <p class="text-xs font-semibold uppercase text-ink-muted">{{ $bucket['label'] }}</p>
                            <p class="mt-1 text-lg font-bold text-ink-heading">{{ $formatMoney($bucket['total']) }}</p>
                            <p class="text-xs text-ink-muted">{{ trans_choice(':count invoice|:count invoices', $bucket['count'], ['count' => $bucket['count']]) }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="flex items-end justify-between gap-3 h-32">
                    @foreach ($data['aging'] as $bucket)
                        <div class="flex-1 flex flex-col items-center gap-2">
                            <div class="w-full flex items-end justify-center h-24">
                                <div class="w-full max-w-12 rounded-t-lg bg-warning" style="height: {{ max(4, ($bucket['total'] / $maxAging) * 100) }}%"></div>
                            </div>
                            <span class="text-[10px] text-ink-muted text-center">{{ $bucket['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </x-entity.section>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <x-entity.section :title="__('Revenue by customer')">
                    <div class="space-y-3">
                        @forelse ($data['revenue_by_customer'] as $row)
                            <div class="flex justify-between text-sm">
                                <span class="text-ink truncate">{{ $row['name'] }}</span>
                                <span class="font-medium text-ink-heading shrink-0">{{ $formatMoney($row['total']) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-ink-muted">{{ __('No payment data.') }}</p>
                        @endforelse
                    </div>
                </x-entity.section>

                <x-entity.section :title="__('Revenue by salesperson')">
                    <div class="space-y-3">
                        @forelse ($data['revenue_by_salesperson'] as $row)
                            <div class="flex justify-between text-sm">
                                <span class="text-ink truncate">{{ $row['name'] }}</span>
                                <span class="font-medium text-ink-heading shrink-0">{{ $formatMoney($row['total']) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-ink-muted">{{ __('No payment data.') }}</p>
                        @endforelse
                    </div>
                </x-entity.section>

                <x-entity.section :title="__('Revenue by product/service')">
                    <div class="space-y-3">
                        @forelse ($data['revenue_by_product'] as $row)
                            <div class="flex justify-between gap-2 text-sm">
                                <span class="text-ink truncate">{{ $row['description'] }}</span>
                                <span class="font-medium text-ink-heading shrink-0">{{ $formatMoney($row['total']) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-ink-muted">{{ __('No line item data.') }}</p>
                        @endforelse
                    </div>
                </x-entity.section>
            </div>

            <x-entity.section :title="__('Revenue by organization')">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <p class="text-xs text-ink-muted">{{ __('Organization') }}</p>
                        <p class="text-sm font-medium text-ink-heading">{{ $data['revenue_by_organization']['name'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ink-muted">{{ __('Revenue collected') }}</p>
                        <p class="text-sm font-medium text-ink-heading">{{ $formatMoney($data['revenue_by_organization']['revenue_collected']) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ink-muted">{{ __('Outstanding') }}</p>
                        <p class="text-sm font-medium text-ink-heading">{{ $formatMoney($data['revenue_by_organization']['outstanding']) }}</p>
                    </div>
                </div>
            </x-entity.section>
        </div>
    </x-layouts.analytics>
</x-app-layout>
