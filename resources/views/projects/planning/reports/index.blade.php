<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Planning Reports')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Planning Reports'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="GET" action="{{ route('projects.planning.reports.index') }}" class="mb-6 rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <x-input-label for="report_type" :value="__('Report')" />
                    <select id="report_type" name="report_type" class="block mt-1 w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                        @foreach ($availableReports as $item)
                            <option value="{{ $item['type'] }}" @selected($reportType === $item['type'])>{{ $item['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="from" :value="__('From')" />
                    <x-text-input id="from" type="date" name="from" class="block mt-1 w-full" :value="$filters['from']" />
                </div>
                <div>
                    <x-input-label for="to" :value="__('To')" />
                    <x-text-input id="to" type="date" name="to" class="block mt-1 w-full" :value="$filters['to']" />
                </div>
                <div>
                    <x-input-label for="project_id" :value="__('Project')" />
                    <select id="project_id" name="project_id" class="block mt-1 w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                        <option value="">{{ __('All projects') }}</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected(($filters['project_id'] ?? null) == $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <x-primary-button type="submit" class="w-full justify-center">{{ __('Generate') }}</x-primary-button>
                </div>
            </div>
        </form>

        <div class="mb-4 flex flex-wrap gap-2">
            @foreach ($exportFormats as $format => $label)
                <a href="{{ route('projects.planning.reports.export', array_merge(request()->query(), ['format' => $format, 'report_type' => $reportType])) }}"
                   class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                    {{ __('Export') }} {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="font-semibold text-slate-900">{{ $report['report_label'] }}</h2>
                <p class="text-xs text-slate-500 mt-1">{{ $report['filters']['from'] }} → {{ $report['filters']['to'] }}</p>
            </div>
            @if (empty($report['rows']))
                <div class="p-12 text-center text-sm text-slate-500">{{ __('No rows for this report.') }}</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                @foreach ($report['columns'] as $column)
                                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ $column['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($report['rows'] as $row)
                                <tr class="hover:bg-slate-50">
                                    @foreach ($report['columns'] as $column)
                                        <td class="px-4 py-3 text-slate-700">{{ $row[$column['key']] ?? '' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </x-layouts.entity-listing>
</x-app-layout>
