<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Attendance Reports')"
        :subtitle="__('Monthly attendance, late, absent, and leave summaries')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('attendance.label'), 'href' => route('hrms.attendance.index')],
                ['label' => __('Reports'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.attendance.index')" variant="secondary" size="sm">{{ __('Calendar') }}</x-ui.button>
            <x-ui.button :href="route('hrms.attendance.summary')" variant="secondary" size="sm">{{ __('Daily Summary') }}</x-ui.button>
        </x-slot:actions>

        <form method="GET" action="{{ route('hrms.attendance.reports.index') }}" class="mb-6 flex flex-wrap items-end gap-3">
            <x-forms.field :label="__('Report')" class="mb-0 min-w-[12rem]">
                <select name="report_type" class="block w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    @foreach ($availableReports as $item)
                        <option value="{{ $item['type'] }}" @selected($reportType === $item['type'])>{{ __($item['label']) }}</option>
                    @endforeach
                </select>
            </x-forms.field>

            <x-forms.field :label="__('Month')" class="mb-0 min-w-[9rem]">
                <select name="month" class="block w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    @foreach ($monthNames as $value => $label)
                        <option value="{{ $value }}" @selected((int) $filters['month'] === (int) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-forms.field>

            <x-forms.field :label="__('Year')" class="mb-0 min-w-[7rem]">
                <x-forms.input type="number" name="year" :value="$filters['year']" min="2000" max="2100" />
            </x-forms.field>

            <x-forms.field :label="__('Department')" class="mb-0 min-w-[12rem]">
                <select name="department_id" class="block w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">{{ __('All departments') }}</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </x-forms.field>

            <x-forms.field :label="__('Employee')" class="mb-0 min-w-[12rem]">
                <select name="employee_id" class="block w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">{{ __('All employees') }}</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((string) ($filters['employee_id'] ?? '') === (string) $employee->id)>{{ $employee->full_name }}</option>
                    @endforeach
                </select>
            </x-forms.field>

            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Generate') }}</x-ui.button>
        </form>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-ink-heading">{{ __($report['report_label']) }}</h2>
                <p class="text-sm text-ink-muted">{{ $report['filters']['month_label'] }} · {{ __('Generated') }} {{ \Carbon\Carbon::parse($report['generated_at'])->format('j M Y g:i A') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($exportFormats as $format => $label)
                    <x-ui.button
                        :href="route('hrms.attendance.reports.export', array_filter([
                            'report_type' => $reportType,
                            'year' => $filters['year'],
                            'month' => $filters['month'],
                            'department_id' => $filters['department_id'],
                            'employee_id' => $filters['employee_id'],
                            'format' => $format,
                        ]))"
                        variant="secondary"
                        size="sm"
                    >{{ __('Export :format', ['format' => $label]) }}</x-ui.button>
                @endforeach
            </div>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ($report['totals'] as $key => $value)
                <x-ui.stat-card :label="__(str_replace('_', ' ', ucfirst($key)))" :value="$value" />
            @endforeach
        </div>

        <x-ui.card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-line text-sm">
                    <thead>
                        <tr>
                            @foreach ($report['columns'] as $column)
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ $column['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($report['rows'] as $row)
                            <tr>
                                @foreach ($report['columns'] as $column)
                                    <td class="px-3 py-2 text-ink-heading">{{ $row[$column['key']] ?? '—' }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ max(1, count($report['columns'])) }}" class="px-3 py-8 text-center text-ink-muted">
                                    {{ __('No records found for the selected filters.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </x-layouts.entity-listing>
</x-app-layout>
