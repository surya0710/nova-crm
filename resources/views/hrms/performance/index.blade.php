<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Performance')"
        :subtitle="__('Configure cycles, competencies, and review foundations')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Performance'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <a href="{{ route('hrms.performance.cycles.index') }}" class="block">
            <x-ui.stat-card :label="__('Cycles')" :value="$cycleCount" />
        </a>
        <a href="{{ route('hrms.performance.competencies.index') }}" class="block">
            <x-ui.stat-card :label="__('Competencies')" :value="$competencyCount" />
        </a>
        <a href="{{ route('hrms.performance.categories.index') }}" class="block">
            <x-ui.stat-card :label="__('Categories')" :value="$categoryCount" />
        </a>
        <a href="{{ route('hrms.performance.templates.index') }}" class="block">
            <x-ui.stat-card :label="__('Templates')" :value="$templateCount" />
        </a>
        <a href="{{ route('hrms.performance.rating-scales.index') }}" class="block">
            <x-ui.stat-card :label="__('Rating Scales')" :value="$scaleCount" />
        </a>
        <x-ui.stat-card :label="__('Active Cycle')" :value="$activeCycle?->name ?? __('None')" />
    </div>
    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden mb-6">
        <div class="px-4 py-3 border-b border-slate-100">
            <h2 class="font-medium text-slate-900">{{ __('Recent Cycles') }}</h2>
        </div>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Name') }}</th>
                    <th class="p-3 text-left">{{ __('Type') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left">{{ __('Dates') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($latestCycles as $cycle)
                <tr class="border-t">
                    <td class="p-3">{{ $cycle->name }}</td>
                    <td class="p-3">{{ config('hrms.performance_cycle_types.'.$cycle->cycle_type, $cycle->cycle_type) }}</td>
                    <td class="p-3">{{ config('hrms.performance_cycle_statuses.'.$cycle->status, $cycle->status) }}</td>
                    <td class="p-3">{{ $cycle->start_date->toDateString() }} – {{ $cycle->end_date->toDateString() }}</td>
                </tr>
            @empty
                <tr class="border-t"><td class="p-3 text-slate-500" colspan="4">{{ __('No performance cycles yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 text-sm text-slate-600">
        {{ __('Performance foundation covers configuration, rating scales, competencies, review templates, and cycles. Employee reviews and scoring arrive in later phases.') }}
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
