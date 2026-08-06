@php
    $healthColors = [
        'on_track' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'at_risk' => 'bg-amber-100 text-amber-800 border-amber-200',
        'delayed' => 'bg-red-100 text-red-800 border-red-200',
        'completed' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'archived' => 'bg-slate-100 text-slate-600 border-slate-200',
    ];
    $statusClass = $healthColors[$snapshot->health_status] ?? 'bg-slate-100 text-slate-600 border-slate-200';
    $metrics = $snapshot->metadata['metrics'] ?? [];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail :title="__('Details Health')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Details Health'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 rounded-xl bg-white border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Health Status') }}</p>
                    <span class="mt-2 inline-flex text-sm font-semibold px-3 py-1 rounded-full border {{ $statusClass }}">
                        {{ $snapshot->health_status_label }}
                    </span>
                </div>
                <div class="text-right">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Completion') }}</p>
                    <p class="mt-1 text-3xl font-bold text-primary-600">{{ $snapshot->completion_percentage }}%</p>
                </div>
            </div>
            <div class="mt-6 w-full bg-slate-100 rounded-full h-3">
                <div class="bg-primary-600 h-3 rounded-full transition-all" style="width: {{ min(100, $snapshot->completion_percentage) }}%"></div>
            </div>
            <dl class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Schedule Variance') }}</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ $snapshot->schedule_variance !== null ? $snapshot->schedule_variance.' '.__('days') : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Budget Variance') }}</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ $snapshot->budget_variance !== null ? number_format((float) $snapshot->budget_variance, 2) : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Est. Completion') }}</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ $snapshot->estimated_completion_date?->format('M j, Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Calculated') }}</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ $snapshot->calculated_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Component Metrics') }}</h3>
            <dl class="space-y-3">
                <div class="flex justify-between text-sm">
                    <dt class="text-slate-500">{{ __('Task completion') }}</dt>
                    <dd class="font-medium text-slate-900">{{ $metrics['task_completion_percentage'] ?? 0 }}%</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-slate-500">{{ __('Milestone completion') }}</dt>
                    <dd class="font-medium text-slate-900">{{ $metrics['milestone_completion_percentage'] ?? 0 }}%</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-slate-500">{{ __('Manual progress') }}</dt>
                    <dd class="font-medium text-slate-900">{{ $metrics['manual_completion_percentage'] ?? 0 }}%</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-slate-500">{{ __('Overdue tasks') }}</dt>
                    <dd class="font-medium {{ ($metrics['overdue_tasks_count'] ?? 0) > 0 ? 'text-red-600' : 'text-slate-900' }}">{{ $metrics['overdue_tasks_count'] ?? 0 }}</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-slate-500">{{ __('Delayed milestones') }}</dt>
                    <dd class="font-medium {{ ($metrics['delayed_milestones_count'] ?? 0) > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ $metrics['delayed_milestones_count'] ?? 0 }}</dd>
                </div>
            </dl>
        </div>
    </div>

    @if ($history->count() > 1)
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Health History') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Date') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Completion') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($history as $entry)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-3 text-sm text-slate-600">{{ $entry->calculated_at?->format('M j, Y g:i A') }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $healthColors[$entry->health_status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $entry->health_status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm font-medium text-slate-900">{{ $entry->completion_percentage }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    </x-layouts.entity-detail>
</x-app-layout>
