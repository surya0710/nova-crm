<x-app-layout>
    @php
        $currency = $data['currency'];
        $formatMoney = fn (float $amount) => number_format($amount, 2).' '.$currency;
        $maxMonthly = max(1, $data['monthly_revenue']->max('total') ?: 1);
        $maxLeadCount = max(1, $data['lead_counts']->max() ?: 1);
    @endphp

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ __('Reports & Analytics') }}</h1>
                <p class="text-sm text-slate-500">{{ $organization->name }}</p>
            </div>
            <form method="GET" class="flex items-center gap-2">
                <select name="period" onchange="this.form.submit()" class="border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="30" @selected($period === '30')>{{ __('Last 30 days') }}</option>
                    <option value="90" @selected($period === '90')>{{ __('Last 90 days') }}</option>
                    <option value="365" @selected($period === '365')>{{ __('Last 12 months') }}</option>
                    <option value="all" @selected($period === 'all')>{{ __('All time') }}</option>
                </select>
                @if (auth()->user()->hasPermission('reports.view') || auth()->user()->hasPermission('finance.view'))
                    <a href="{{ route('reports.finance') }}" class="text-sm text-indigo-600 hover:text-indigo-800 whitespace-nowrap">{{ __('Finance reports') }}</a>
                @endif
            </form>
        </div>
    </x-slot>

    {{-- Revenue overview --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach ([
            ['label' => __('Revenue collected'), 'value' => $formatMoney($data['revenue_collected']), 'sub' => __('From recorded :label', ['label' => strtolower(crm_term('payments'))]), 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
            ['label' => __('Outstanding'), 'value' => $formatMoney($data['outstanding_amount']), 'sub' => __(':count unpaid :label', ['count' => $data['outstanding_count'], 'label' => strtolower(crm_term('invoices'))]), 'bg' => 'bg-amber-50', 'text' => 'text-amber-600'],
            ['label' => __('Pipeline value'), 'value' => $formatMoney($data['open_pipeline_value']), 'sub' => __('Open :label', ['label' => strtolower(crm_term('deals'))]), 'bg' => 'bg-indigo-50', 'text' => 'text-indigo-600'],
            ['label' => __('Win rate'), 'value' => $data['conversion_rate'] !== null ? $data['conversion_rate'].'%' : '—', 'sub' => __(':label closed won vs lost', ['label' => crm_term('leads')]), 'bg' => 'bg-violet-50', 'text' => 'text-violet-600'],
        ] as $stat)
            <div class="rounded-xl bg-white border border-slate-200 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $stat['value'] }}</p>
                <p class="mt-2 text-xs text-slate-400">{{ $stat['sub'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Monthly revenue --}}
        <div class="xl:col-span-2 rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Monthly revenue') }}</h3>
                <p class="text-sm text-slate-500 mt-0.5">{{ __('Collected :label over the last 6 months', ['label' => strtolower(crm_term('payments'))]) }}</p>
            </div>
            <div class="p-6">
                @if ($data['monthly_revenue']->every(fn ($m) => $m['total'] == 0))
                    <p class="text-sm text-slate-500 text-center py-8">{{ __('No revenue recorded yet.') }}</p>
                @else
                    <div class="flex items-end justify-between gap-3 h-48">
                        @foreach ($data['monthly_revenue'] as $month)
                            <div class="flex-1 flex flex-col items-center gap-2 min-w-0">
                                <span class="text-xs font-medium text-slate-600 truncate w-full text-center">{{ number_format($month['total'], 0) }}</span>
                                <div class="w-full flex items-end justify-center h-32">
                                    <div
                                        class="w-full max-w-12 rounded-t-lg bg-indigo-500 transition-all"
                                        style="height: {{ max(4, ($month['total'] / $maxMonthly) * 100) }}%"
                                        title="{{ $formatMoney($month['total']) }}"
                                    ></div>
                                </div>
                                <span class="text-[10px] text-slate-500 truncate w-full text-center">{{ $month['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Payment methods --}}
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Payment methods') }}</h3>
                <p class="text-sm text-slate-500 mt-0.5">{{ __('Breakdown for selected period') }}</p>
            </div>
            <div class="p-6">
                @if ($data['payments_by_method']->isEmpty())
                    <p class="text-sm text-slate-500 text-center py-8">{{ __('No payments in this period.') }}</p>
                @else
                    <div class="space-y-4">
                        @foreach ($data['payments_by_method'] as $row)
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-medium text-slate-700">{{ config('payments.methods.'.$row->method, ucfirst(str_replace('_', ' ', $row->method))) }}</span>
                                    <span class="text-slate-600">{{ $formatMoney((float) $row->total) }}</span>
                                </div>
                                <p class="text-xs text-slate-400">{{ trans_choice(':count payment|:count payments', $row->count, ['count' => $row->count]) }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Lead funnel --}}
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __(':label funnel', ['label' => crm_term('leads')]) }}</h3>
                <p class="text-sm text-slate-500 mt-0.5">{{ __(':count total', ['count' => $data['lead_total']]) }}</p>
            </div>
            <div class="p-6 space-y-3">
                @forelse (config('leads.statuses') as $status => $label)
                    @php $count = (int) ($data['lead_counts'][$status] ?? 0); @endphp
                    @if ($count > 0 || in_array($status, ['new', 'won', 'lost']))
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-slate-700">{{ $label }}</span>
                                <span class="font-medium text-slate-900">{{ $count }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full bg-indigo-500" style="width: {{ ($count / $maxLeadCount) * 100 }}%"></div>
                            </div>
                        </div>
                    @endif
                @empty
                    <p class="text-sm text-slate-500">{{ __('No leads yet.') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Pipeline stages --}}
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ crm_term('pipeline') }}</h3>
                <p class="text-sm text-slate-500 mt-0.5">{{ __('Opportunities by stage') }}</p>
            </div>
            <div class="p-6 space-y-3">
                @php $hasOpportunities = $data['opportunity_by_stage']->isNotEmpty(); @endphp
                @if ($hasOpportunities)
                    @foreach (config('pipeline.stages') as $stage => $label)
                        @php
                            $row = $data['opportunity_by_stage'][$stage] ?? null;
                            $count = $row ? (int) $row->count : 0;
                            $value = $row ? (float) $row->value : 0;
                        @endphp
                        <div class="flex items-center justify-between gap-4 py-2 border-b border-slate-50 last:border-0">
                            <div>
                                <p class="text-sm font-medium text-slate-700">{{ $label }}</p>
                                <p class="text-xs text-slate-400">{{ trans_choice(':count deal|:count deals', $count, ['count' => $count]) }}</p>
                            </div>
                            <p class="text-sm font-semibold text-slate-900 shrink-0">{{ $formatMoney($value) }}</p>
                        </div>
                    @endforeach
                @else
                    <p class="text-sm text-slate-500">{{ __('No pipeline data yet.') }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Invoices --}}
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ crm_term('invoices') }}</h3>
            </div>
            <div class="p-6 space-y-2">
                @foreach (config('invoices.statuses') as $status => $label)
                    @php $count = (int) ($data['invoice_counts'][$status] ?? 0); @endphp
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-600">{{ $label }}</span>
                        <span class="font-medium text-slate-900">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Quotations --}}
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ crm_term('quotations') }}</h3>
            </div>
            <div class="p-6 space-y-2">
                @foreach (config('quotations.statuses') as $status => $label)
                    @php $count = (int) ($data['quotation_counts'][$status] ?? 0); @endphp
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-600">{{ $label }}</span>
                        <span class="font-medium text-slate-900">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Top performers --}}
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Top performers') }}</h3>
                <p class="text-sm text-slate-500 mt-0.5">{{ __('Won :label by assignee', ['label' => strtolower(crm_term('leads'))]) }}</p>
            </div>
            <div class="p-6">
                @if ($data['top_performers']->isEmpty())
                    <p class="text-sm text-slate-500">{{ __('No closed wins yet.') }}</p>
                @else
                    <div class="space-y-3">
                        @foreach ($data['top_performers'] as $performer)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-700">{{ $performer['name'] }}</span>
                                <span class="text-sm font-semibold text-indigo-600">{{ $performer['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
