@php
    $healthColors = [
        'on_track' => 'bg-emerald-100 text-emerald-800',
        'at_risk' => 'bg-amber-100 text-amber-800',
        'delayed' => 'bg-red-100 text-red-800',
        'completed' => 'bg-indigo-100 text-indigo-800',
        'archived' => 'bg-slate-100 text-slate-600',
    ];
    $healthBarColors = [
        'on_track' => 'bg-emerald-500',
        'at_risk' => 'bg-amber-500',
        'delayed' => 'bg-red-500',
        'completed' => 'bg-indigo-500',
        'archived' => 'bg-slate-400',
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail :title="__('Executive')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Executive'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Active Projects') }}</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ $activeProjects }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('At Risk / Delayed') }}</p>
            <p class="mt-1 text-3xl font-bold text-amber-600">{{ $atRiskCount }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('On Track') }}</p>
            <p class="mt-1 text-3xl font-bold text-emerald-600">{{ $portfolioHealth['on_track'] ?? 0 }}</p>
        </div>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6 mb-6">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Portfolio Health Distribution') }}</h3>
        @php $total = max(1, array_sum($portfolioHealth)); @endphp
        <div class="flex h-4 rounded-full overflow-hidden bg-slate-100">
            @foreach ($healthStatuses as $key => $label)
                @if (($portfolioHealth[$key] ?? 0) > 0)
                    <div
                        class="{{ $healthBarColors[$key] ?? 'bg-slate-400' }} h-full"
                        style="width: {{ (($portfolioHealth[$key] ?? 0) / $total) * 100 }}%"
                        title="{{ $label }}: {{ $portfolioHealth[$key] ?? 0 }}"
                    ></div>
                @endif
            @endforeach
        </div>
        <div class="mt-4 flex flex-wrap gap-4">
            @foreach ($healthStatuses as $key => $label)
                <div class="flex items-center gap-2 text-xs text-slate-600">
                    <span class="w-3 h-3 rounded-full {{ $healthBarColors[$key] ?? 'bg-slate-400' }}"></span>
                    <span>{{ $label }}: <strong>{{ $portfolioHealth[$key] ?? 0 }}</strong></span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-semibold text-slate-900">{{ __('Projects by Health') }}</h3>
        </div>
        @if ($snapshots->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No health snapshots yet. Open a project health view to calculate metrics.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Project') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Manager') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Health') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Completion') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($snapshots as $snapshot)
                            @if ($snapshot->project)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-medium text-slate-900">{{ $snapshot->project->name }}</p>
                                        @if ($snapshot->project->status)
                                            <span class="text-xs text-slate-500">{{ $snapshot->project->status->name }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">{{ $snapshot->project->manager?->name ?? '—' }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $healthColors[$snapshot->health_status] ?? 'bg-slate-100 text-slate-600' }}">
                                            {{ $snapshot->health_status_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $snapshot->completion_percentage }}%</td>
                                    <td class="px-6 py-4 text-right space-x-3">
                                        <a href="{{ route('projects.progress.dashboard', $snapshot->project) }}" class="text-sm text-primary-600 hover:text-primary-700">{{ __('Dashboard') }}</a>
                                        <a href="{{ route('projects.show', $snapshot->project) }}" class="text-sm text-slate-600 hover:text-slate-800">{{ __('View') }}</a>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    </x-layouts.entity-detail>
</x-app-layout>
