<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Recruitment Reports')"
        :subtitle="__('Executive and operational reports')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Recruitment Reports'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="GET" action="{{ route('hrms.recruitment.reports.index') }}" class="flex flex-wrap items-end gap-3 mb-6">
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Report') }}</label>
            <select name="report_type" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                @foreach ($availableReports as $item)
                    <option value="{{ $item['type'] }}" @selected($reportType === $item['type'])>{{ __($item['label']) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Period') }}</label>
            <select name="period" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                @foreach ($periods as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['period'] ?? 'month') === $value)>{{ __($label) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('From') }}</label>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('To') }}</label>
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
        </div>
        <button type="submit" class="rounded-lg bg-slate-900 text-white text-sm px-4 py-2">{{ __('Generate') }}</button>
        @can('recruitment.reports.export')
            <a href="{{ route('hrms.recruitment.exports.index', ['report_type' => $reportType, 'period' => $filters['period'] ?? 'month']) }}" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">{{ __('Export') }}</a>
        @endcan
    </form>

    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-medium text-slate-900">{{ $report['report_label'] }}</h2>
            <span class="text-xs text-slate-500">{{ $report['generated_at'] }}</span>
        </div>
        <pre class="text-xs text-slate-700 whitespace-pre-wrap overflow-x-auto max-h-[32rem]">{{ json_encode($report['data'], JSON_PRETTY_PRINT) }}</pre>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
