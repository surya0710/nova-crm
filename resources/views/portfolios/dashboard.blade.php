@php
    $healthBarColors = [
        'on_track' => 'bg-emerald-500',
        'at_risk' => 'bg-amber-500',
        'delayed' => 'bg-red-500',
        'completed' => 'bg-indigo-500',
        'archived' => 'bg-slate-400',
    ];
    $healthStatuses = config('projects.health_statuses', []);
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail :title="__('Dashboard')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Dashboard'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Projects') }}</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ $statistics['project_count'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Avg. Completion') }}</p>
            <p class="mt-1 text-3xl font-bold text-primary-600">{{ $statistics['average_completion_percentage'] ?? 0 }}%</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Open Risks') }}</p>
            <p class="mt-1 text-3xl font-bold text-amber-600">{{ $portfolio->risks->whereNotIn('status', ['closed', 'accepted'])->count() }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Open Issues') }}</p>
            <p class="mt-1 text-3xl font-bold text-red-600">{{ $portfolio->issues->whereNotIn('status', ['closed', 'resolved'])->count() }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Health Distribution') }}</h3>
            @php $health = $statistics['health'] ?? []; $total = max(1, array_sum($health)); @endphp
            <div class="flex h-4 rounded-full overflow-hidden bg-slate-100">
                @foreach ($healthStatuses as $key => $label)
                    @if (($health[$key] ?? 0) > 0)
                        <div class="{{ $healthBarColors[$key] ?? 'bg-slate-400' }} h-full" style="width: {{ (($health[$key] ?? 0) / $total) * 100 }}%"></div>
                    @endif
                @endforeach
            </div>
            <div class="mt-4 flex flex-wrap gap-4">
                @foreach ($healthStatuses as $key => $label)
                    <div class="flex items-center gap-2 text-xs text-slate-600">
                        <span class="w-3 h-3 rounded-full {{ $healthBarColors[$key] ?? 'bg-slate-400' }}"></span>
                        {{ $label }}: <strong>{{ $health[$key] ?? 0 }}</strong>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Budget Summary') }}</h3>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Planned') }}</dt>
                    <dd class="text-lg font-semibold text-slate-900">{{ number_format($statistics['budget']['planned_total'] ?? 0, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Actual') }}</dt>
                    <dd class="text-lg font-semibold text-slate-900">{{ number_format($statistics['budget']['actual_total'] ?? 0, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Forecast') }}</dt>
                    <dd class="text-lg font-semibold text-slate-900">{{ number_format($statistics['budget']['forecast_total'] ?? 0, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Variance') }}</dt>
                    <dd class="text-lg font-semibold {{ ($statistics['budget']['variance_total'] ?? 0) > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ number_format($statistics['budget']['variance_total'] ?? 0, 2) }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-semibold text-slate-900">{{ __('Projects by Status') }}</h3>
        </div>
        @if ($portfolio->projects->isEmpty())
            <div class="p-8 text-center text-sm text-slate-500">{{ __('No projects in this portfolio.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Project') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Completion') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($portfolio->projects as $project)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $project->name }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $project->status?->name ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-2 rounded-full bg-slate-100 max-w-[120px]">
                                            <div class="h-full rounded-full bg-primary-600" style="width: {{ min(100, $project->completion_percentage ?? 0) }}%"></div>
                                        </div>
                                        <span class="text-sm text-slate-600">{{ $project->completion_percentage ?? 0 }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('projects.show', $project) }}" class="text-sm text-primary-600 hover:text-primary-700">{{ __('View') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    </x-layouts.entity-detail>
</x-app-layout>
