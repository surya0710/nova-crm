@php
    $density = $shellNav['density'] ?? 'comfortable';
    $previewColumns = [__('Employee'), __('Net'), __('Status')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Payroll Preview')" :subtitle="__('Preview uses the same calculation engine as production and does not persist results.')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Preview'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.payroll.runs.index')" variant="secondary" size="sm">{{ __('Back to runs') }}</x-ui.button>
        </x-slot:actions>

        <x-ui.card class="mb-4">
            <form method="POST" action="{{ route('hrms.payroll.runs.preview.submit') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                @csrf
                <x-forms.field :label="__('Payroll period')" name="payroll_period_id">
                    <x-forms.select name="payroll_period_id" required>
                        <option value="">{{ __('Payroll period') }}</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}" @selected(($selectedPeriodId ?? null) == $period->id)>{{ $period->name }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Employee')" name="employee_id">
                    <x-forms.select name="employee_id">
                        <option value="">{{ __('All eligible employees') }}</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected(($selectedEmployeeId ?? null) == $employee->id)>{{ $employee->full_name }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <div class="flex items-end">
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Preview') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        @if ($preview)
            @if ($preview['mode'] === 'employee')
                @php($result = $preview['result'])
                <x-entity.section :title="$result['employee']['name'] . ' (' . $result['employee']['employee_code'] . ')'">
                    @if ($result['validation_errors'])
                        <ul class="space-y-1 text-sm text-danger">
                            @foreach ($result['validation_errors'] as $error)
                                <li>{{ $error['code'] }}: {{ $error['message'] }}</li>
                            @endforeach
                        </ul>
                    @elseif ($result['calculation'])
                        <x-entity.definition-list>
                            <x-entity.definition-item :label="__('Gross')">{{ number_format($result['calculation']['gross_salary'], 2) }}</x-entity.definition-item>
                            <x-entity.definition-item :label="__('Deductions')">{{ number_format($result['calculation']['total_deductions'], 2) }}</x-entity.definition-item>
                            <x-entity.definition-item :label="__('Net')">{{ number_format($result['calculation']['net_salary'], 2) }}</x-entity.definition-item>
                            <x-entity.definition-item :label="__('Payable Days')">{{ $result['calculation']['payable_days'] }}</x-entity.definition-item>
                        </x-entity.definition-list>
                        <p class="mt-3 font-mono text-xs text-ink-muted">{{ $result['calculation']['calculation_hash'] }}</p>
                    @endif
                </x-entity.section>
            @else
                @php($periodPreview = $preview['result'])
                <x-ui.card class="mb-4">
                    <p class="text-sm text-ink-muted">{{ __('Success') }}: {{ $periodPreview['success_count'] }} · {{ __('Errors') }}: {{ $periodPreview['error_count'] }}</p>
                </x-ui.card>
                <x-tables.table :columns="$previewColumns" :dense="$density === 'compact'" sticky>
                    @foreach ($periodPreview['employees'] as $row)
                        <tr class="hover:bg-surface-muted/60 transition">
                            <td class="px-4 py-3 text-sm text-ink-heading">{{ $row['employee']['name'] }}</td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ $row['calculation'] ? number_format($row['calculation']['net_salary'], 2) : '—' }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if ($row['validation_errors'])
                                    <span class="text-danger">{{ $row['validation_errors'][0]['message'] }}</span>
                                @else
                                    <x-ui.badge variant="success">{{ __('OK') }}</x-ui.badge>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-tables.table>
            @endif
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
