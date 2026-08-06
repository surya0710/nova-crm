@php
    $lineColumns = [__('Item'), ['label' => __('Amount'), 'align' => 'right']];
    $density = $shellNav['density'] ?? 'comfortable';
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Payslip')"
        :subtitle="$payslip->payslip_number"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('My HR'), 'href' => route('ess.dashboard')],
                ['label' => __('Payroll'), 'href' => route('ess.payroll.index')],
                ['label' => $payslip->payslip_number, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('ess.payroll.download', $payslip)" variant="primary" size="sm">{{ __('Download PDF') }}</x-ui.button>
            <x-ui.button :href="route('ess.payroll.index')" variant="secondary" size="sm">{{ __('Back') }}</x-ui.button>
        </x-slot:actions>

        @include('ess.partials.nav')

        <x-entity.section :title="__('Summary')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Period')">{{ $payslip->payrollRun?->period?->name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Net Salary')">{{ number_format((float) $payslip->net_salary, 2) }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Generated')">{{ $payslip->generated_at?->toDateString() ?? '—' }}</x-entity.definition-item>
            </x-entity.definition-list>
        </x-entity.section>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-ui.card>
                <x-entity.section :title="__('Earnings')">
                    <x-tables.table :columns="$lineColumns" :dense="$density === 'compact'">
                        @foreach ($earnings as $line)
                            <tr class="border-t border-line">
                                <td class="px-4 py-2 text-sm text-ink-heading">{{ $line['name'] ?? $line['code'] }}</td>
                                <td class="px-4 py-2 text-sm text-ink-muted text-right">{{ number_format((float) ($line['amount'] ?? 0), 2) }}</td>
                            </tr>
                        @endforeach
                    </x-tables.table>
                </x-entity.section>
            </x-ui.card>
            <x-ui.card>
                <x-entity.section :title="__('Deductions')">
                    <x-tables.table :columns="$lineColumns" :dense="$density === 'compact'">
                        @foreach ($deductions as $line)
                            <tr class="border-t border-line">
                                <td class="px-4 py-2 text-sm text-ink-heading">{{ $line['name'] ?? $line['code'] }}</td>
                                <td class="px-4 py-2 text-sm text-ink-muted text-right">{{ number_format((float) ($line['amount'] ?? 0), 2) }}</td>
                            </tr>
                        @endforeach
                    </x-tables.table>
                </x-entity.section>
            </x-ui.card>
            <x-ui.card>
                <x-entity.section :title="__('Employer Contributions')">
                    <x-tables.table :columns="$lineColumns" :dense="$density === 'compact'">
                        @foreach ($employerContributions as $line)
                            <tr class="border-t border-line">
                                <td class="px-4 py-2 text-sm text-ink-heading">{{ $line['name'] ?? $line['code'] }}</td>
                                <td class="px-4 py-2 text-sm text-ink-muted text-right">{{ number_format((float) ($line['amount'] ?? 0), 2) }}</td>
                            </tr>
                        @endforeach
                    </x-tables.table>
                </x-entity.section>
            </x-ui.card>
        </div>
    </x-layouts.entity-detail>
</x-app-layout>
