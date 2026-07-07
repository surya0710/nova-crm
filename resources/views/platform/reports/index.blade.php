<x-platform-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-lg font-semibold">{{ __('Platform Reports') }}</h1>
            <a href="{{ route('platform.reports.export', $filters) }}" class="text-sm rounded-lg bg-violet-600 hover:bg-violet-500 px-3 py-1.5">{{ __('Export CSV') }}</a>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
            <div class="text-sm text-slate-400">{{ __('Revenue Managed') }}</div>
            <div class="text-2xl font-bold mt-1">{{ number_format($report['revenue_managed']['total'], 2) }}</div>
        </div>
        <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
            <div class="text-sm text-slate-400">{{ __('Invoices') }}</div>
            <div class="text-2xl font-bold mt-1">{{ number_format($report['invoices']['total']) }}</div>
        </div>
        <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
            <div class="text-sm text-slate-400">{{ __('Payments') }}</div>
            <div class="text-2xl font-bold mt-1">{{ number_format($report['payments']['total'], 2) }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach ([
            'organizations_growth' => __('Organizations Growth'),
            'users_growth' => __('Users Growth'),
            'lead_volume' => __('Lead Volume'),
            'customer_growth' => __('Customer Growth'),
        ] as $key => $title)
            <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-800 font-medium">{{ $title }}</div>
                <div class="divide-y divide-slate-800 text-sm">
                    @forelse ($report[$key] as $row)
                        <div class="px-5 py-2 flex justify-between"><span class="text-slate-400">{{ $row->period }}</span><span class="text-white">{{ $row->count }}</span></div>
                    @empty
                        <div class="px-5 py-6 text-slate-500 text-center">{{ __('No data') }}</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 rounded-xl bg-slate-900 border border-slate-800 overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-800 font-medium">{{ __('Top Active Organizations (30 days)') }}</div>
        <div class="divide-y divide-slate-800 text-sm">
            @forelse ($report['top_active_organizations'] as $org)
                <div class="px-5 py-2 flex justify-between">
                    <span class="text-white">{{ $org->name }}</span>
                    <span class="text-slate-400">{{ $org->activity_count ?? 0 }} {{ __('events') }}</span>
                </div>
            @empty
                <div class="px-5 py-6 text-slate-500 text-center">{{ __('No data') }}</div>
            @endforelse
        </div>
    </div>
</x-platform-layout>
