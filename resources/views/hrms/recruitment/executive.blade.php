<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Executive Recruitment Summary')"
        :subtitle="__('Leadership KPI overview')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Executive Recruitment Summary'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @include('hrms.recruitment.partials.analytics-filters', [
        'action' => route('hrms.recruitment.executive'),
        'filters' => $filters,
        'periods' => $periods,
    ])

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">
        @foreach ($executive['kpis'] as $key => $value)
            @continue(is_array($value))
            <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
                <p class="text-sm text-slate-500">{{ __(str_replace('_', ' ', ucfirst($key))) }}</p>
                <p class="text-2xl font-semibold text-slate-900 mt-1">{{ $value ?? '—' }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
            <h2 class="font-medium text-slate-900 mb-3">{{ __('Hiring Manager Performance') }}</h2>
            <pre class="text-xs text-slate-700 whitespace-pre-wrap">{{ json_encode($executive['hiring_manager'], JSON_PRETTY_PRINT) }}</pre>
        </div>
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
            <h2 class="font-medium text-slate-900 mb-3">{{ __('Time Analytics') }}</h2>
            <dl class="space-y-2 text-sm">
                @foreach ($executive['time_metrics'] as $key => $value)
                    <div class="flex justify-between border-b border-slate-100 py-1">
                        <dt class="text-slate-500">{{ __(str_replace('_', ' ', ucfirst($key))) }}</dt>
                        <dd class="font-medium">{{ $value ?? '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
