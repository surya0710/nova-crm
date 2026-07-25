@php
    $reports = [
        'summary' => __('Summary'),
        'statutory' => __('Statutory Liability'),
        'salary_register' => __('Salary Register'),
        'department' => __('Department Salary'),
        'cost_center' => __('Cost Center'),
        'ledger' => __('Ledger'),
    ];
    $activeReport = $filters['report'] ?? 'summary';
    $density = $shellNav['density'] ?? 'comfortable';
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Payroll Finance Reports')" :subtitle="__('Analyze payroll finance data across published runs')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Reports'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:filters>
            <div class="mb-4 flex flex-wrap gap-2">
                @foreach ($reports as $key => $label)
                    <x-ui.button
                        :href="route('hrms.payroll.reports.index', array_merge($filters, ['report' => $key]))"
                        :variant="$activeReport === $key ? 'primary' : 'secondary'"
                        size="sm"
                    >{{ $label }}</x-ui.button>
                @endforeach
            </div>
            <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <input type="hidden" name="report" value="{{ $activeReport }}">
                <x-forms.field :label="__('Payroll run')" name="payroll_run_id">
                    <x-forms.select name="payroll_run_id">
                        <option value="">{{ __('All published runs') }}</option>
                        @foreach ($publishedRuns as $run)
                            <option value="{{ $run->id }}" @selected(($filters['payroll_run_id'] ?? null) == $run->id)>{{ $run->period?->name }} ({{ $run->status }})</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <div class="flex items-end">
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Apply') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($activeReport === 'summary' && empty($data))
            <x-ui.card><x-ui.empty-state-preset variant="reports" /></x-ui.card>
        @else
            <x-ui.card :padding="false">
                @if ($activeReport === 'summary')
                    <div class="divide-y divide-line">
                        @foreach ($data as $key => $value)
                            <div class="flex justify-between px-5 py-3 text-sm">
                                <span class="text-ink-muted">{{ str_replace('_', ' ', ucfirst($key)) }}</span>
                                <span class="font-medium text-ink-heading">{{ is_numeric($value) ? number_format((float) $value, 2) : $value }}</span>
                            </div>
                        @endforeach
                    </div>
                @elseif ($activeReport === 'statutory')
                    @if (empty($data))
                        <div class="p-5"><x-ui.empty-state-preset variant="reports" /></div>
                    @else
                        <x-tables.table :columns="[__('Account'), __('Liability')]" :dense="$density === 'compact'">
                            @foreach ($data as $code => $amount)
                                <tr class="hover:bg-surface-muted/60 transition">
                                    <td class="px-4 py-3 font-mono text-sm text-ink-heading">{{ $code }}</td>
                                    <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </x-tables.table>
                    @endif
                @elseif ($activeReport === 'salary_register')
                    @if (empty($data))
                        <div class="p-5"><x-ui.empty-state-preset variant="reports" :title="__('No salary register data.')" /></div>
                    @else
                        <x-tables.table :columns="[__('Employee'), __('Period'), __('Gross'), __('Deductions'), __('Net')]" :dense="$density === 'compact'">
                            @foreach ($data as $row)
                                <tr class="hover:bg-surface-muted/60 transition">
                                    <td class="px-4 py-3 text-sm text-ink-heading">{{ $row['employee_name'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $row['period'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) ($row['gross_salary'] ?? 0), 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) ($row['total_deductions'] ?? 0), 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) ($row['net_salary'] ?? 0), 2) }}</td>
                                </tr>
                            @endforeach
                        </x-tables.table>
                    @endif
                @elseif (in_array($activeReport, ['department', 'cost_center'], true))
                    @if (empty($data))
                        <div class="p-5"><x-ui.empty-state-preset variant="reports" :title="__('No data.')" /></div>
                    @else
                        <x-tables.table :columns="[$activeReport === 'department' ? __('Department') : __('Cost center'), __('Employees'), __('Gross'), __('Net')]" :dense="$density === 'compact'">
                            @foreach ($data as $row)
                                <tr class="hover:bg-surface-muted/60 transition">
                                    <td class="px-4 py-3 text-sm text-ink-heading">{{ $row['department'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $row['employee_count'] ?? 0 }}</td>
                                    <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) ($row['gross_salary'] ?? 0), 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) ($row['net_salary'] ?? 0), 2) }}</td>
                                </tr>
                            @endforeach
                        </x-tables.table>
                    @endif
                @elseif ($activeReport === 'ledger')
                    @if (empty($data))
                        <div class="p-5"><x-ui.empty-state-preset variant="reports" :title="__('No ledger entries.')" /></div>
                    @else
                        <x-tables.table :columns="[__('Account'), __('Type'), __('Amount'), __('Employee'), __('Description')]" :dense="$density === 'compact'">
                            @foreach ($data as $entry)
                                <tr class="hover:bg-surface-muted/60 transition">
                                    <td class="px-4 py-3 text-sm"><span class="font-mono text-xs">{{ $entry->account_code }}</span> {{ $entry->account_name }}</td>
                                    <td class="px-4 py-3 text-sm capitalize text-ink-muted">{{ $entry->entry_type }}</td>
                                    <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $entry->amount, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $entry->employee?->full_name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $entry->description }}</td>
                                </tr>
                            @endforeach
                        </x-tables.table>
                    @endif
                @endif
            </x-ui.card>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
