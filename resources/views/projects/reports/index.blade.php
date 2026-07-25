<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Reports')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Reports'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    @can('generateReports', $project)
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-6">
            <h3 class="text-sm font-semibold text-slate-900 mb-3">{{ __('Generate Report') }}</h3>
            <form method="POST" action="{{ route('projects.reports.store', $project) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Report Type') }}</label>
                    <select name="report_type" class="block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm" required>
                        @foreach ($reportTypes as $key => $label)
                            <option value="{{ $key }}" @selected(old('report_type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('report_type')" class="mt-1" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Format') }}</label>
                    <select name="format" class="block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm" required>
                        @foreach ($reportFormats as $key => $label)
                            <option value="{{ $key }}" @selected(old('format', 'pdf') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('format')" class="mt-1" />
                </div>
                <x-primary-button type="submit">{{ __('Generate') }}</x-primary-button>
            </form>
        </div>
    @endcan

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-semibold text-slate-900">{{ __('Generated Reports') }}</h3>
        </div>
        @if ($reports->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No reports generated yet.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Type') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Generated') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('By') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($reports as $report)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $report->report_type_label }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $report->generated_at?->format('M j, Y g:i A') }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $report->generator?->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-right">
                                    @if ($report->storage_path)
                                        <a href="{{ route('projects.reports.download', [$project, $report]) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">{{ __('Download') }}</a>
                                    @else
                                        <span class="text-xs text-slate-400">{{ __('Unavailable') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($reports->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">{{ $reports->links() }}</div>
            @endif
        @endif
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
