<x-app-layout>
    <x-flash-messages />

    <x-layouts.settings
        :title="__('Recruitment Exports')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Recruitment Exports'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 max-w-2xl">
        <p class="text-sm text-slate-600 mb-4">{{ __('Export respects the selected report type and period filters. PDF export is a future placeholder.') }}</p>
        <form method="POST" action="{{ route('hrms.recruitment.exports.download') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Report') }}</label>
                <select name="report_type" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                    @foreach ($availableReports as $item)
                        <option value="{{ $item['type'] }}" @selected(request('report_type') === $item['type'])>{{ __($item['label']) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Format') }}</label>
                <select name="format" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                    @foreach ($formats as $value => $label)
                        <option value="{{ $value }}" @disabled($value === 'pdf')>{{ __($label) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Period') }}</label>
                    <select name="period" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        @foreach ($periods as $value => $label)
                            <option value="{{ $value }}" @selected(request('period', 'month') === $value)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('From') }}</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('To') }}</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
            </div>
            <button type="submit" class="rounded-lg bg-slate-900 text-white text-sm px-4 py-2">{{ __('Download') }}</button>
        </form>
    </div>
    </x-layouts.settings>
</x-app-layout>
