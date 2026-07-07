<x-platform-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold">{{ __('Platform Dashboard') }}</h1>
    </x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
        <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
            <div class="text-sm text-slate-400">{{ __('Organizations') }}</div>
            <div class="text-3xl font-bold mt-1">{{ number_format($metrics['organizations']['total']) }}</div>
            <div class="text-xs text-slate-500 mt-1">{{ $metrics['organizations']['active'] }} {{ __('active') }}</div>
        </div>
        <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
            <div class="text-sm text-slate-400">{{ __('Total Users') }}</div>
            <div class="text-3xl font-bold mt-1">{{ number_format($metrics['users']['total']) }}</div>
            <div class="text-xs text-slate-500 mt-1">{{ $metrics['users']['active_today'] }} {{ __('active today') }}</div>
        </div>
        <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
            <div class="text-sm text-slate-400">{{ __('New Orgs This Month') }}</div>
            <div class="text-3xl font-bold mt-1">{{ number_format($metrics['organizations']['new_this_month']) }}</div>
        </div>
        <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
            <div class="text-sm text-slate-400">{{ __('Storage Usage') }}</div>
            <div class="text-3xl font-bold mt-1">{{ number_format($metrics['placeholders']['storage_usage'] / 1048576, 1) }} MB</div>
            <div class="text-xs text-slate-500 mt-1">{{ __('Across all tenants') }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @foreach (['leads' => __('Leads Today'), 'customers' => __('Customers Today'), 'invoices' => __('Invoices Today'), 'payments' => __('Payments Today')] as $key => $label)
            <div class="rounded-xl bg-slate-900/60 border border-slate-800 p-4">
                <div class="text-sm text-slate-400">{{ $label }}</div>
                <div class="text-2xl font-semibold mt-1">{{ number_format($metrics['activity_today'][$key]) }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
        <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
            <div class="text-sm font-medium text-slate-300 mb-2">{{ __('API Requests') }}</div>
            <div class="text-slate-500 text-sm">{{ __('Placeholder — integration pending') }}</div>
        </div>
        <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
            <div class="text-sm font-medium text-slate-300 mb-2">{{ __('Queue Health') }}</div>
            <div class="text-emerald-400 text-sm capitalize">{{ $metrics['placeholders']['queue_health'] }}</div>
        </div>
    </div>

    <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-800 font-medium">{{ __('Recent Organization Activity') }}</div>
        <div class="divide-y divide-slate-800">
            @forelse ($metrics['recent_activity'] as $activity)
                <div class="px-5 py-3 text-sm flex justify-between gap-4">
                    <div>
                        <span class="text-white">{{ $activity['organization'] ?? __('Unknown') }}</span>
                        <span class="text-slate-500"> — {{ $activity['event'] }}</span>
                        @if ($activity['subject'])
                            <span class="text-slate-400 block text-xs">{{ $activity['subject'] }}</span>
                        @endif
                    </div>
                    <span class="text-slate-500 shrink-0">{{ $activity['created_at']?->diffForHumans() }}</span>
                </div>
            @empty
                <div class="px-5 py-8 text-center text-slate-500">{{ __('No recent activity.') }}</div>
            @endforelse
        </div>
    </div>
</x-platform-layout>
