<x-app-layout>
    @php
        $currency = $data['currency'];
        $formatMoney = fn (float $amount) => number_format($amount, 2).' '.$currency;
        $maxMonthly = max(1, collect($data['revenue_by_month'])->max('total') ?: 1);
        $maxAging = max(1, collect($data['aging'])->max('total') ?: 1);
    @endphp

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ __('Accounts Receivable & Revenue') }}</h1>
                <p class="text-sm text-slate-500">{{ $organization->name }}</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('reports.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">{{ __('General reports') }}</a>
                @if (auth()->user()->hasPermission('reports.manage') || auth()->user()->hasPermission('finance.view'))
                    <a href="{{ route('reports.export.revenue', request()->query()) }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        {{ __('Export revenue CSV') }}
                    </a>
                    <a href="{{ route('reports.export.outstanding', request()->query()) }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        {{ __('Export outstanding CSV') }}
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    {{-- Filters --}}
    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
            <div>
                <x-input-label for="period" :value="__('Period')" />
                <select id="period" name="period" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="30" @selected(($filters['period'] ?? '30') === '30')>{{ __('Last 30 days') }}</option>
                    <option value="90" @selected(($filters['period'] ?? '') === '90')>{{ __('Last 90 days') }}</option>
                    <option value="365" @selected(($filters['period'] ?? '') === '365')>{{ __('Last 12 months') }}</option>
                    <option value="all" @selected(($filters['period'] ?? '') === 'all')>{{ __('All time') }}</option>
                    <option value="custom" @selected(($filters['period'] ?? '') === 'custom')>{{ __('Custom range') }}</option>
                </select>
            </div>
            <div>
                <x-input-label for="date_from" :value="__('From')" />
                <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="$filters['date_from'] ?? ''" />
            </div>
            <div>
                <x-input-label for="date_to" :value="__('To')" />
                <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full" :value="$filters['date_to'] ?? ''" />
            </div>
            <div>
                <x-input-label for="customer_id" :value="crm_term('customer')" />
                <select id="customer_id" name="customer_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('All customers') }}</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) ($filters['customer_id'] ?? '') === (string) $customer->id)>{{ $customer->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="salesperson_id" :value="__('Salesperson')" />
                <select id="salesperson_id" name="salesperson_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('All salespeople') }}</option>
                    @foreach ($salespeople as $person)
                        <option value="{{ $person->id }}" @selected((string) ($filters['salesperson_id'] ?? '') === (string) $person->id)>{{ $person->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="status" :value="__('Invoice status')" />
                <select id="status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (config('invoices.statuses') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2 lg:col-span-6">
                <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
            </div>
        </form>
    </div>

    {{-- Dashboard KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach ([
            ['label' => __('Outstanding receivables'), 'value' => $formatMoney($data['outstanding_receivables']), 'sub' => trans_choice(':count invoice|:count invoices', $data['outstanding_count'], ['count' => $data['outstanding_count']])],
            ['label' => __('Total paid'), 'value' => $formatMoney($data['total_paid']), 'sub' => __('Collected in selected period')],
            ['label' => __('Total invoiced'), 'value' => $formatMoney($data['total_invoiced']), 'sub' => trans_choice(':count invoice|:count invoices', $data['invoice_count'], ['count' => $data['invoice_count']])],
            ['label' => __('Collected this month'), 'value' => $formatMoney($data['collected_this_month']), 'sub' => now()->format('F Y')],
        ] as $stat)
            <div class="rounded-xl bg-white border border-slate-200 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $stat['value'] }}</p>
                <p class="mt-2 text-xs text-slate-400">{{ $stat['sub'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach ([
            ['label' => __('Paid invoices'), 'value' => number_format($data['paid_invoice_count'])],
            ['label' => __('Average invoice value'), 'value' => $formatMoney($data['average_invoice_value'])],
            ['label' => __('Average payment value'), 'value' => $formatMoney($data['average_payment_value'])],
            ['label' => __('Collection rate'), 'value' => $data['collection']['collection_rate'] !== null ? $data['collection']['collection_rate'].'%' : '—'],
        ] as $stat)
            <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                <p class="mt-1 text-xl font-bold text-slate-900">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Revenue by month --}}
        <div class="xl:col-span-2 rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Revenue by month') }}</h3>
            </div>
            <div class="p-6">
                @if (collect($data['revenue_by_month'])->every(fn ($m) => $m['total'] == 0))
                    <p class="text-sm text-slate-500 text-center py-8">{{ __('No revenue recorded yet.') }}</p>
                @else
                    <div class="flex items-end justify-between gap-2 h-48 overflow-x-auto">
                        @foreach ($data['revenue_by_month'] as $month)
                            <div class="flex-1 flex flex-col items-center gap-2 min-w-[3rem]">
                                <span class="text-xs font-medium text-slate-600">{{ number_format($month['total'], 0) }}</span>
                                <div class="w-full flex items-end justify-center h-32">
                                    <div class="w-full max-w-10 rounded-t-lg bg-indigo-500" style="height: {{ max(4, ($month['total'] / $maxMonthly) * 100) }}%"></div>
                                </div>
                                <span class="text-[10px] text-slate-500 text-center">{{ $month['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Collection metrics --}}
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Collection metrics') }}</h3>
            </div>
            <div class="p-6 space-y-3">
                @foreach ([
                    ['label' => __('Collection rate'), 'value' => $data['collection']['collection_rate'] !== null ? $data['collection']['collection_rate'].'%' : '—'],
                    ['label' => __('Outstanding %'), 'value' => $data['collection']['outstanding_percent'] !== null ? $data['collection']['outstanding_percent'].'%' : '—'],
                    ['label' => __('Paid %'), 'value' => $data['collection']['paid_percent'] !== null ? $data['collection']['paid_percent'].'%' : '—'],
                    ['label' => __('Avg. days to payment'), 'value' => $data['collection']['average_days_to_payment'] !== null ? $data['collection']['average_days_to_payment'] : '—'],
                    ['label' => __('Payment count'), 'value' => number_format($data['collection']['payment_count'])],
                ] as $row)
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-600">{{ $row['label'] }}</span>
                        <span class="font-medium text-slate-900">{{ $row['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Invoice aging --}}
    <div class="mt-6 rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-semibold text-slate-900">{{ __('Invoice aging') }}</h3>
            <p class="text-sm text-slate-500 mt-0.5">{{ __('Outstanding balances by days overdue') }}</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-5 gap-4 mb-6">
                @foreach ($data['aging'] as $bucket)
                    <div class="text-center p-4 rounded-lg bg-slate-50">
                        <p class="text-xs font-semibold uppercase text-slate-500">{{ $bucket['label'] }}</p>
                        <p class="mt-1 text-lg font-bold text-slate-900">{{ $formatMoney($bucket['total']) }}</p>
                        <p class="text-xs text-slate-400">{{ trans_choice(':count invoice|:count invoices', $bucket['count'], ['count' => $bucket['count']]) }}</p>
                    </div>
                @endforeach
            </div>
            <div class="flex items-end justify-between gap-3 h-32">
                @foreach ($data['aging'] as $bucket)
                    <div class="flex-1 flex flex-col items-center gap-2">
                        <div class="w-full flex items-end justify-center h-24">
                            <div class="w-full max-w-12 rounded-t-lg bg-amber-500" style="height: {{ max(4, ($bucket['total'] / $maxAging) * 100) }}%"></div>
                        </div>
                        <span class="text-[10px] text-slate-500 text-center">{{ $bucket['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Revenue by customer --}}
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Revenue by customer') }}</h3>
            </div>
            <div class="p-6 space-y-3">
                @forelse ($data['revenue_by_customer'] as $row)
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-700 truncate">{{ $row['name'] }}</span>
                        <span class="font-medium text-slate-900 shrink-0">{{ $formatMoney($row['total']) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('No payment data.') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Revenue by salesperson --}}
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Revenue by salesperson') }}</h3>
            </div>
            <div class="p-6 space-y-3">
                @forelse ($data['revenue_by_salesperson'] as $row)
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-700 truncate">{{ $row['name'] }}</span>
                        <span class="font-medium text-slate-900 shrink-0">{{ $formatMoney($row['total']) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('No payment data.') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Revenue by product --}}
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Revenue by product/service') }}</h3>
            </div>
            <div class="p-6 space-y-3">
                @forelse ($data['revenue_by_product'] as $row)
                    <div class="flex justify-between gap-2 text-sm">
                        <span class="text-slate-700 truncate">{{ $row['description'] }}</span>
                        <span class="font-medium text-slate-900 shrink-0">{{ $formatMoney($row['total']) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('No line item data.') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Organization summary --}}
    <div class="mt-6 rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-semibold text-slate-900">{{ __('Revenue by organization') }}</h3>
        </div>
        <div class="p-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-slate-500">{{ __('Organization') }}</p>
                <p class="text-sm font-medium text-slate-900">{{ $data['revenue_by_organization']['name'] }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">{{ __('Revenue collected') }}</p>
                <p class="text-sm font-medium text-slate-900">{{ $formatMoney($data['revenue_by_organization']['revenue_collected']) }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">{{ __('Outstanding') }}</p>
                <p class="text-sm font-medium text-slate-900">{{ $formatMoney($data['revenue_by_organization']['outstanding']) }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
