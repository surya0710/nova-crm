@php
    $healthBarColors = [
        'on_track' => 'bg-emerald-500',
        'at_risk' => 'bg-amber-500',
        'delayed' => 'bg-red-500',
        'completed' => 'bg-indigo-500',
        'archived' => 'bg-slate-400',
    ];
    $healthStatuses = config('projects.health_statuses', []);
    $kpis = $dashboard['kpis'] ?? [];
    $portfolioHealth = $dashboard['portfolio_health'] ?? [];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail :title="__('Details Executive')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Details Executive'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Active Projects') }}</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ $kpis['active_projects'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('At Risk / Delayed') }}</p>
            <p class="mt-1 text-3xl font-bold text-amber-600">{{ $kpis['at_risk_count'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('On Track') }}</p>
            <p class="mt-1 text-3xl font-bold text-emerald-600">{{ $kpis['on_track_count'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Open Risks') }}</p>
            <p class="mt-1 text-3xl font-bold text-red-600">{{ $kpis['open_risks'] ?? 0 }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Avg. Completion') }}</p>
            <p class="mt-1 text-2xl font-bold text-primary-600">{{ $kpis['average_completion_percentage'] ?? 0 }}%</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Budget Variance') }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($kpis['budget_variance_total'] ?? 0, 0) }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Completed') }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $kpis['completed_count'] ?? 0 }}</p>
        </div>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6 mb-6">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Portfolio Health Distribution') }}</h3>
        @php $total = max(1, array_sum($portfolioHealth)); @endphp
        <div class="flex h-4 rounded-full overflow-hidden bg-slate-100">
            @foreach ($healthStatuses as $key => $label)
                @if (($portfolioHealth[$key] ?? 0) > 0)
                    <div class="{{ $healthBarColors[$key] ?? 'bg-slate-400' }} h-full" style="width: {{ (($portfolioHealth[$key] ?? 0) / $total) * 100 }}%" title="{{ $label }}: {{ $portfolioHealth[$key] ?? 0 }}"></div>
                @endif
            @endforeach
        </div>
        <div class="mt-4 flex flex-wrap gap-4">
            @foreach ($healthStatuses as $key => $label)
                <div class="flex items-center gap-2 text-xs text-slate-600">
                    <span class="w-3 h-3 rounded-full {{ $healthBarColors[$key] ?? 'bg-slate-400' }}"></span>
                    {{ $label }}: <strong>{{ $portfolioHealth[$key] ?? 0 }}</strong>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('At-Risk Projects') }}</h3>
            </div>
            @forelse ($dashboard['at_risk_projects'] ?? [] as $item)
                <div class="px-6 py-3 border-b border-slate-100 last:border-0 flex items-center justify-between gap-3">
                    <span class="text-sm text-slate-900">{{ $item['name'] ?? '—' }}</span>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">{{ $item['health_status'] ?? '' }}</span>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-slate-500">{{ __('No at-risk projects.') }}</div>
            @endforelse
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Upcoming Milestones') }}</h3>
            </div>
            @forelse ($dashboard['upcoming_milestones'] ?? [] as $milestone)
                <div class="px-6 py-3 border-b border-slate-100 last:border-0">
                    <p class="text-sm font-medium text-slate-900">{{ $milestone['name'] ?? '—' }}</p>
                    <p class="text-xs text-slate-500">{{ $milestone['project_name'] ?? '' }} · {{ $milestone['due_date'] ?? '' }}</p>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-slate-500">{{ __('No upcoming milestones.') }}</div>
            @endforelse
        </div>
    </div>

    @if (! empty($dashboard['portfolios']))
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Portfolio Rollup') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Portfolio') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Projects') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Completion') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Risk Score') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($dashboard['portfolios'] as $item)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $item['name'] ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $item['stats']['project_count'] ?? 0 }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $item['stats']['average_completion_percentage'] ?? 0 }}%</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $item['stats']['risk_score'] ?? 0 }}</td>
                                <td class="px-6 py-4 text-right">
                                    @if (! empty($item['id']))
                                        <a href="{{ route('portfolios.show', $item['id']) }}" class="text-sm text-primary-600 hover:text-primary-700">{{ __('View') }}</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    </x-layouts.entity-detail>
</x-app-layout>
