<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Tax Reports')" :subtitle="__('TDS register, projections, and compliance summaries')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Income Tax'), 'href' => route('hrms.payroll.tax.index')],
                ['label' => __('Reports'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-ui.card class="mb-6">
            <form method="GET" action="{{ route('hrms.payroll.tax.reports.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <x-forms.field :label="__('Report Type')" name="type">
                    <x-forms.select name="type">
                        @foreach ($reportTypes as $key => $label)
                            <option value="{{ $key }}" @selected($reportType === $key)>{{ __($label) }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Financial Year')" name="tax_financial_year_id">
                    <x-forms.select name="tax_financial_year_id">
                        <option value="">{{ __('All / current') }}</option>
                        @foreach ($financialYears as $fy)
                            <option value="{{ $fy->id }}" @selected($financialYearId == $fy->id)>{{ $fy->label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <div class="flex items-end gap-2 md:col-span-2">
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Run Report') }}</x-ui.button>
                    <x-ui.button :href="route('hrms.payroll.tax.reports.export', ['type' => $reportType, 'format' => 'csv', 'tax_financial_year_id' => $financialYearId])" variant="secondary" size="sm">{{ __('Export CSV') }}</x-ui.button>
                    <x-ui.button :href="route('hrms.payroll.tax.reports.export', ['type' => $reportType, 'format' => 'xlsx', 'tax_financial_year_id' => $financialYearId])" variant="secondary" size="sm">{{ __('Export XLSX') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card :padding="false">
            <x-tables.table :columns="$report['headers'] ?? []">
                @forelse ($report['data'] ?? [] as $row)
                    <tr class="hover:bg-surface-muted/60 transition">
                        @foreach ($report['headers'] ?? [] as $header)
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ $row[$header] ?? '—' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ max(1, count($report['headers'] ?? [])) }}" class="px-4 py-8"><x-ui.empty-state-preset variant="payroll" /></td></tr>
                @endforelse
            </x-tables.table>
        </x-ui.card>
    </x-layouts.entity-listing>
</x-app-layout>
