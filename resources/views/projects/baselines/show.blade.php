@php
    $driftClass = fn ($days) => match (true) {
        $days === null => 'text-slate-600',
        $days > 0 => 'text-red-600',
        $days < 0 => 'text-emerald-600',
        default => 'text-slate-600',
    };
    $deltaClass = fn ($val) => match (true) {
        $val > 0 => 'text-red-600',
        $val < 0 => 'text-emerald-600',
        default => 'text-slate-600',
    };
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail :title="__('Details Baselines')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Details Baselines'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6 mb-6">
        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Baseline') }}</dt>
                <dd class="mt-1 font-medium text-slate-900">{{ $baseline->name ?: __('Baseline :version', ['version' => $baseline->version]) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Captured By') }}</dt>
                <dd class="mt-1 text-slate-900">{{ $baseline->creator?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Captured At') }}</dt>
                <dd class="mt-1 text-slate-900">{{ $baseline->created_at->format('M j, Y g:i A') }}</dd>
            </div>
        </dl>
        @if ($baseline->notes)
            <p class="mt-4 text-sm text-slate-600 border-t border-slate-100 pt-4">{{ $baseline->notes }}</p>
        @endif
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Scope Variance') }}</h3>
            </div>
            <dl class="p-6 space-y-4">
                <div class="flex justify-between">
                    <dt class="text-sm text-slate-600">{{ __('Milestones') }}</dt>
                    <dd class="text-sm font-medium {{ $deltaClass($comparison['scope']['milestone_delta'] ?? 0) }}">
                        {{ $comparison['scope']['baseline_milestone_count'] ?? 0 }} → {{ $comparison['scope']['current_milestone_count'] ?? 0 }}
                        ({{ ($comparison['scope']['milestone_delta'] ?? 0) >= 0 ? '+' : '' }}{{ $comparison['scope']['milestone_delta'] ?? 0 }})
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-slate-600">{{ __('Tasks') }}</dt>
                    <dd class="text-sm font-medium {{ $deltaClass($comparison['scope']['task_delta'] ?? 0) }}">
                        {{ $comparison['scope']['baseline_task_count'] ?? 0 }} → {{ $comparison['scope']['current_task_count'] ?? 0 }}
                        ({{ ($comparison['scope']['task_delta'] ?? 0) >= 0 ? '+' : '' }}{{ $comparison['scope']['task_delta'] ?? 0 }})
                    </dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Schedule Variance') }}</h3>
            </div>
            <dl class="p-6 space-y-4">
                <div class="flex justify-between">
                    <dt class="text-sm text-slate-600">{{ __('Baseline Planned End') }}</dt>
                    <dd class="text-sm font-medium text-slate-900">{{ $comparison['schedule']['baseline_planned_end_date'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-slate-600">{{ __('Current Planned End') }}</dt>
                    <dd class="text-sm font-medium text-slate-900">{{ $comparison['schedule']['current_planned_end_date'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-slate-600">{{ __('Drift (days)') }}</dt>
                    <dd class="text-sm font-semibold {{ $driftClass($comparison['schedule']['drift_days'] ?? null) }}">
                        {{ $comparison['schedule']['drift_days'] !== null ? (($comparison['schedule']['drift_days'] >= 0 ? '+' : '').$comparison['schedule']['drift_days']) : '—' }}
                    </dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Budget Variance') }}</h3>
            </div>
            <dl class="p-6 space-y-4">
                <div class="flex justify-between">
                    <dt class="text-sm text-slate-600">{{ __('Baseline Estimated') }}</dt>
                    <dd class="text-sm font-medium text-slate-900">{{ number_format($comparison['budget']['baseline_estimated'] ?? 0, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-slate-600">{{ __('Current Estimated') }}</dt>
                    <dd class="text-sm font-medium text-slate-900">{{ number_format($comparison['budget']['current_estimated'] ?? 0, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-slate-600">{{ __('Current Actual') }}</dt>
                    <dd class="text-sm font-medium text-slate-900">{{ number_format($comparison['budget']['current_actual'] ?? 0, 2) }}</dd>
                </div>
                <div class="flex justify-between border-t border-slate-100 pt-4">
                    <dt class="text-sm font-medium text-slate-700">{{ __('Estimated Delta') }}</dt>
                    <dd class="text-sm font-semibold {{ $deltaClass($comparison['budget']['estimated_delta'] ?? 0) }}">{{ number_format($comparison['budget']['estimated_delta'] ?? 0, 2) }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Progress Variance') }}</h3>
            </div>
            <dl class="p-6 space-y-4">
                <div class="flex justify-between">
                    <dt class="text-sm text-slate-600">{{ __('Baseline Completion') }}</dt>
                    <dd class="text-sm font-medium text-slate-900">{{ $comparison['progress']['baseline_completion_percentage'] ?? 0 }}%</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-slate-600">{{ __('Current Completion') }}</dt>
                    <dd class="text-sm font-medium text-slate-900">{{ $comparison['progress']['current_completion_percentage'] ?? 0 }}%</dd>
                </div>
                <div class="flex justify-between border-t border-slate-100 pt-4">
                    <dt class="text-sm font-medium text-slate-700">{{ __('Delta') }}</dt>
                    <dd class="text-sm font-semibold {{ $deltaClass($comparison['progress']['delta'] ?? 0) }}">
                        {{ ($comparison['progress']['delta'] ?? 0) >= 0 ? '+' : '' }}{{ $comparison['progress']['delta'] ?? 0 }}%
                    </dd>
                </div>
            </dl>
        </div>
    </div>
    </x-layouts.entity-detail>
</x-app-layout>
